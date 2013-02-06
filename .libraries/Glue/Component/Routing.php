<?php
namespace Glue\Component;

/**
 * Component to handle routing
 *
 * @listen glue.component.environment.process.pre > onPreProcess()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Routing extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to provide registry
	 *
	 * @object \Glue\Entity\Registry
	 */
	private $registry = NULL;

	/**
	 * Private property to store modifier
	 */
	private $modifier = NULL;

	/**
	 * Event listener
	 */
	final public function onPreProcess() {
		if($this->modifier !== NULL) {
			\Glue\Component\Environment::getInstance()->register('modifier.routing', $this->modifier);
		}
	}


	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$this->dispatcher->addListener(array(&$this, 'onPreProcess'), 'glue.component.environment.process.pre');

			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ);

			$modifier  = array();
			$parameter = array();
			$settings  = \Glue\Component\Configuration::getInstance()->get(__CLASS__);
			$method    = \Glue\Component\Request::getInstance()->get('method');
			$uri       = (isset($_REQUEST['Glue']['node'])) ? '/' . \Glue\Helper\Modifier::cleanPath($_REQUEST['Glue']['node'], true) : '/';

			if(isset($settings['@attributes']['i18n']) && $settings['@attributes']['i18n'] === true) {
				$path         = \Glue\Factory::getInstance()->get('\Glue\Core')->path;
				$language     = \Glue\Component\Url::getInstance()->get('switches.language') ?: \Glue\Component\Configuration::getInstance()->get('\Glue\Component\Environment\defaults\language');
				$dependencies = array();

				foreach($path as $scope) {
					$dependencies[] = $scope . '/.internationalization/tree/' . $language . '.xml';
				}

				$id = $path['local'] . '/.cache/' . __CLASS__ . '/' . sha1(serialize($language));

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

							$temp = \Glue\Helper\General::merge($temp, $this->_parse($tree['childnodes']));
						}

						$data = array(array_keys($temp), array_values($temp));

						$cache->setData($data)->set();

						unset($temp, $tree);
					}
				}

                $uri = $_REQUEST['Glue']['node'] = preg_replace($data[0], $data[1], $uri);

				unset($path, $language, $dependencies, $data);
			}

			if(isset($settings['route'])) {
				$routes = array();

				if(isset($settings['route']['pattern'])) {
					$settings['route'] = array($settings['route']);
				}

				foreach((array) $settings['route'] as $route) {
					$methods  = (isset($route['@attributes']['methods'])) ? explode(',', strtoupper(preg_replace('/\s/', '', $route['@attributes']['methods']))) : NULL;

					if(isset($route['pattern']) && isset($route['target']) && ($methods === NULL || in_array($method, $methods))) {
						$routes['/^' . str_replace('/', '\/', $route['pattern']) . '$/'] = $route['target'];
					}

					if(isset($route['@attributes']['modifier'])) {
						$modifier = array_merge($modifier, explode(',', preg_replace('/\s/', '', $route['@attributes']['modifier'])));
					}
				}

				if(count($routes) > 0) {
					$uri = preg_replace(array_keys($routes), $routes, $uri);
				}

				$uri = parse_url(urldecode($uri));

				if(isset($uri['query'])) {
					parse_str($uri['query'], $uriParameter);

					$parameter = array_merge($parameter, $uriParameter);
					$modifier  = array_flip($modifier);

					foreach($parameter as $key => $value) {
						if(isset($modifier[$key]) === true) {
							$modifier[$key] = $value;
						} else {
							unset($modifier[$key]);
						}
					}

					unset($uriParameter, $key, $value);
				} else {
					$modifier = array();
				}

				$_REQUEST['Glue']['node'] = \Glue\Helper\Modifier::cleanPath($uri['path']);

				if(count($modifier) > 0) {
					$this->modifier = $modifier;
				}

				unset($routes, $route, $methods);
			}

			$this->registry->set(NULL, $parameter);

			unset($modifier, $parameter, $settings, $method, $uri);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to parse tree
	 *
	 * @param array $tree
	 * @param array $parent [optional]
	 *
	 * @return array
	 *
	 * @throw \RuntimeException
	 */
	protected function &_parse(array &$tree, $parent = NULL) {
		try {
			$return = array();

			foreach($tree as $index => $subtree) {
				if(isset($tree[$index]['@attributes']) && is_array($tree[$index]['@attributes'])) {
					foreach($tree[$index]['@attributes'] as $key => $value) {
						$tree[$index][$key] = $value;
					}

					unset($tree[$index]['@attributes']);
				}

				$tree[$index]['slug'] = (isset($tree[$index]['slug'])) ? \Glue\Helper\Modifier::sluggify($tree[$index]['slug']) : \Glue\Helper\Modifier::sluggify($tree[$index]['title']);

				$node = ($parent === NULL) ? '/' . $tree[$index]['node'] : $parent['node'] . '/' . $tree[$index]['node'];
				$slug = ($parent === NULL) ? '/' . \Glue\Helper\Modifier::sluggify($tree[$index]['slug'] ?: $tree[$index]['title']) : $parent['slug'] . '/' . \Glue\Helper\Modifier::sluggify($tree[$index]['slug'] ?: $tree[$index]['title']);

				if($slug !== $node) {
					$return['/^' . preg_quote($slug, '/') . '/i'] = $node;
				}

				if(isset($subtree['childnodes']) && is_array($subtree['childnodes']) && count($subtree['childnodes']) > 0) {
					if(!isset($subtree['childnodes'][0])) {
						$tree[$index]['childnodes'] = array($tree[$index]['childnodes']);
					}

					foreach($tree[$index]['childnodes'] as $index2 => $subtree2) {
						$tree[$index]['childnodes'][$index2]['parent'] =& $tree[$index];
					}

					$return = \Glue\Helper\General::merge($return, $this->_parse($tree[$index]['childnodes'], array('node' => $node, 'slug' => $slug)));
				}
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
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
	final public function __call($method, $arguments) {
		if(method_exists($this->registry, $method) === true) {
			return call_user_func_array(array(&$this->registry, $method), (array) $arguments);
		}
	}
}
