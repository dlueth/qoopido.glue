<?php
namespace Glue\Module;

/**
 * Tree module
 *
 * @event glue.module.tree.load.pre(array $files) > load()
 * @event glue.module.tree.load.post(array $files, array $data) > load()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Tree extends \Glue\Abstracts\Base {
	/**
	 * Property to provide registry
	 */
	protected $registry = NULL;

	/**
	 * Property to store environment
	 */
	protected static $environment = NULL;

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
			self::$environment = \Glue\Component\Environment::getInstance();
			self::$path        = self::$environment->get('path');
			self::$node        = '/' . self::$environment->get('node');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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
	public function load($language, $scope = GLUE_SCOPE_ALL) {
		$language = (is_string($language)) ? (array) $language : $language;

		if(($result = \Glue\Helper\validator::batch(array(
			'@$language' => array($language, 'isString'),
			'$scope'     => array($scope, array('matchesBitmask', array(GLUE_SCOPE_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.pre', array($language)));

			$dependencies = array();

			switch($scope) {
				case GLUE_SCOPE_GLOBAL:
					foreach($language as $code) {
						$dependencies[] = self::$path['global'] . '/.internationalization/tree/' . $code . '.xml';
					}
					break;
				case GLUE_SCOPE_LOCAL:
					foreach($language as $code) {
						$dependencies[] = self::$path['local'] . '/.internationalization/tree/' . $code . '.xml';
					}
					break;
				case GLUE_SCOPE_ALL:
					foreach(self::$path as $scope) {
						foreach($language as $code) {
							$dependencies[] = $scope . '/.internationalization/tree/' . $code . '.xml';
						}
					}
					break;
			}

			$id = self::$path['local'] . '/.cache/' . __CLASS__ . '/' . sha1(serialize($language));

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

						$temp[$tree['@attributes']['id']]               =  array();
						$temp[$tree['@attributes']['id']]['nodelist']   =  array();
						$temp[$tree['@attributes']['id']]['current']    =  NULL;
						$temp[$tree['@attributes']['id']]['childnodes'] =& $this->_processTree($tree['childnodes'], $temp[$tree['@attributes']['id']]);

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
					$this->_processState($tree);

					if(isset($tree['breadcrumb'])) {
						$this->_processBreadcrumb($tree, $tree['breadcrumb']);
						$tree['current'] =& $tree['breadcrumb'][count($tree['breadcrumb']) - 1];
					}
				}

				$this->registry->set(NULL, $data);
			}

			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.post', array($language, $data)));

			unset($language, $scope, $result, $dependencies, $id, $cache, $data);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to process state for tree
	 *
	 * @param array $tree
	 * @param array $result
	 *
	 * @throw \RuntimeException
	 */
	protected function _processState(array &$tree) {
		try {
			foreach($tree['nodelist'] as &$item) {
				$node  = '/' . $item['node'];
				$state = (preg_match('/^' . preg_quote($node, '/') . '.*/', self::$node . '/')) ? (($node == self::$node) ? 2 : 1) : 0;

				if($state !== 0) {
					$item['state'] = $state;

					if($state === 2) {
						$tree['current'] =& $item;
					}
				}

				unset($item, $node, $state);
			}

			unset($tree);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
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
	protected function _processBreadcrumb(array &$tree, array &$result) {
		try {
			if(isset($tree['childnodes'])) {
				foreach($tree['childnodes'] as &$item) {
					if($item['state'] > 0) {
						$result[] =& $item;

						if(isset($item['childnodes'])) {
							$this->_processBreadcrumb($item, $result);
						}

						break;
					}
				}

				unset($item, $node, $status);
			}

			unset($tree, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to parse tree
	 *
	 * @param array $tree
	 * @param array $root
	 *
	 * @return array
	 *
	 * @throw \RuntimeException
	 */
	protected function &_processTree(array &$tree, array &$root) {
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
				$tree[$index]['state']   =  0;
				$tree[$index]['custom']  = array();

				$root['nodelist'][$tree[$index]['slug']] =& $tree[$index];

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

					$tree[$index]['childnodes'] =& $this->_processTree($tree[$index]['childnodes'], $root);
				}
			}

			return $tree;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
