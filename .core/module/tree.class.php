<?php
namespace Glue\Module;

/**
 * Tree module
 *
 * @event glue.module.tree.load.pre(array $files) > load()
 * @event glue.module.tree.load.post(array $files, array $data) > load()
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
class Tree extends \Glue\Abstracts\Base {
	/**
	 * Property to provide registry
	 */
	protected $registry = NULL;

	/**
	 * Property to store path
	 */
	protected static $path = NULL;

	/**
	 * Property to store node
	 */
	protected static $node = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$path = \Glue\Component\Environment::getInstance()->get('path');
			self::$node = '/' . \Glue\Component\Environment::getInstance()->get('node');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize() {
		try {
			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Magic method to allow registry access
	 *
	 * @param string $method
	 * @param mixed $arguments
	 *
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		if(method_exists($this->registry, $method) === true) {
			return call_user_func_array(array(&$this->registry, $method), (array) $arguments);
		}
	}

	/**
	 * Method to load tree files
	 *
	 * @param mixed $language
	 * @param int $scope [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function load($language, $scope = SCOPE_ALL) {
		$language = (is_string($language)) ? (array) $language : $language;

		if(($result = \Glue\Helper\validator::batch(array(
			'@$language' => array($language, 'isString'),
			'$scope'     => array($scope, array('matchesBitmask', array(SCOPE_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.pre', array($language)));

			$dependencies = array();

			switch($scope) {
				case SCOPE_GLOBAL:
					foreach($language as $code) {
						$dependencies[] = self::$path['global'] . '/.internationalization/tree/' . $code . '.xml';
					}
					break;
				case SCOPE_LOCAL:
					foreach($language as $code) {
						$dependencies[] = self::$path['local'] . '/.internationalization/tree/' . $code . '.xml';
					}
					break;
				case SCOPE_ALL:
					foreach(self::$path as $scope) {
						foreach($language as $code) {
							$dependencies[] = $scope . '/.internationalization/tree/' . $code . '.xml';
						}
					}
					break;
			}

			$id = self::$path['local'] . '/.cache/' . strtolower(__CLASS__) . '/' . sha1(serialize($language));

			if(extension_loaded('apc') === true) {
				$cache = \Glue\Entity\Cache\Apc::getInstance($id);
			} else {
				$cache = \Glue\Entity\Cache\File::getInstance($id);
			}

			$cache->setDependencies($dependencies);

			if(($data = $cache->get()) === false) {
				if(($data = \Glue\Helper\General::loadConfiguration($cache->dependencies)) !== false && isset($data['Tree'])) {
					$temp = array();

					if(!isset($data['Tree'][0])) {
						$data['Tree'] = array($data['Tree']);
					}

					foreach($data['Tree'] as $tree) {
						if(!isset($tree['childnodes'][0])) {
							$tree['childnodes'] = array($tree['childnodes']);
						}

						$temp[$tree['@attributes']['id']] = array();
						$temp[$tree['@attributes']['id']]['childnodes'] =& $this->_parse($tree['childnodes']);

						if(isset($tree['@attributes']['breadcrumb']) && $tree['@attributes']['breadcrumb'] == true) {
							$temp[$tree['@attributes']['id']]['breadcrumb'] = array();
						}

						unset($tree['@attributes']);
					}

					$data = $temp;

					$cache->setData($data)->set();

					unset($temp, $tree);
				}
			}

			if($data !== false) {
				foreach($data as &$tree) {
					if(isset($tree['breadcrumb'])) {
						$this->_breadcrumb($tree, $tree['breadcrumb']);
						$tree['current'] =& $tree['breadcrumb'][count($tree['breadcrumb']) - 1];
					}
				}

				$this->registry->set(NULL, $data);
			}

			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.post', array($language, $data)));

			unset($language, $scope, $result, $dependencies, $id, $cache, $data);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to process breadcrumb for tree
	 *
	 * @param array $tree
	 * @param array $result
	 *
	 * @throw \RuntimeException
	 */
	protected function _breadcrumb(array &$tree, array &$result) {
		try {
			if(isset($tree['childnodes'])) {
				foreach($tree['childnodes'] as &$item) {
					$node   = '/' . $item['node'];
					$status = (preg_match('/^' . preg_quote($node, '/') . '.*/', self::$node . '/')) ? 1 : 0;

					if($status === 1) {
						$status = ($node == self::$node) ? 2 : $status;

						$result[] =& $item;

						$item['status'] = $status;

						if($status === 1 && isset($item['childnodes'])) {
							$this->_breadcrumb($item, $result);
						}

						break;
					}
				}

				unset($item, $node, $status);
			}

			unset($tree, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to parse tree
	 *
	 * @param array $tree
	 *
	 * @return array
	 *
	 * @throw \RuntimeException
	 */
	protected function &_parse(array &$tree) {
		try {
			foreach($tree as $index => $subtree) {
				if(isset($tree[$index]['@attributes']) && is_array($tree[$index]['@attributes'])) {
					foreach($tree[$index]['@attributes'] as $key => $value) {
						$tree[$index][$key] = $value;
					}

					unset($tree[$index]['@attributes']);
				}

				$tree[$index]['slug'] = (isset($tree[$index]['slug'])) ? \Glue\Helper\Modifier::sluggify($tree[$index]['slug']) : \Glue\Helper\Modifier::sluggify($tree[$index]['title']);
				$pointer =& $tree[$index];

				while(isset($pointer['parent'])) {
					$pointer = &$pointer['parent'];

					$tree[$index]['node'] = $pointer['node'] . '/' . $tree[$index]['node'];
					$tree[$index]['slug'] = $pointer['slug'] . '/' . $tree[$index]['slug'];
				}

				$tree[$index]['alias']   = str_replace('/', '.', $tree[$index]['node']);
				$tree[$index]['visible'] =  (!isset($subtree['@attributes']['visible']) || !is_bool($subtree['@attributes']['visible'])) ? true : $subtree['@attributes']['visible'];
				$tree[$index]['status']  =  0;
				$tree[$index]['custom']  = array();

				if(isset($subtree['custom'])) {
					if(isset($subtree['custom']['@attributes']) && is_array($subtree['custom']['@attributes'])) {
						foreach($subtree['custom']['@attributes'] as $key => $value) {
							$subtree['custom'][$key] = $value;
						}

						unset($subtree['custom']['@attributes']);
					}

					$tree[$index]['custom'] = $subtree['custom'];
				}

				if(isset($subtree['childnodes']) && is_array($subtree['childnodes']) && count($subtree['childnodes']) > 0) {
					if(!isset($subtree['childnodes'][0])) {
						$tree[$index]['childnodes'] = array($tree[$index]['childnodes']);
					}

					foreach($tree[$index]['childnodes'] as $index2 => $subtree2) {
						$tree[$index]['childnodes'][$index2]['parent'] =& $tree[$index];
					}

					$tree[$index]['childnodes'] =& $this->_parse($tree[$index]['childnodes']);
				}
			}

			return $tree;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>