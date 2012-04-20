<?php
namespace Glue\Components;

/**
 * Component to handle routing
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Routing extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$settings = \Glue\Components\Configuration::getInstance()->get(__CLASS__);
			$method   = \Glue\Components\Request::getInstance()->get('method');
			$uri      = (isset($_REQUEST['Glue']['node'])) ? '/' . \Glue\Helper\Modifier::cleanPath($_REQUEST['Glue']['node'], true) : '/';

			if(isset($settings['@attributes']['i18n']) && $settings['@attributes']['i18n'] === true) {
				$path         = \Glue\Factory::getInstance()->get('\Glue\Core')->path;
				$language     = \Glue\Components\Url::getInstance()->get('switches.language') ?: \Glue\Components\Configuration::getInstance()->get('\Glue\Components\Environment\defaults\language');
				$dependencies = array();

				foreach($path as $scope) {
					$dependencies[] = $scope . '/.internationalization/tree/' . $language . '.xml';
				}

				$id = $path['local'] . '/.cache/' . strtolower(__CLASS__) . '/' . sha1(serialize($language));

				if(extension_loaded('apc') === true) {
					$cache = \Glue\Objects\Cache\Apc::getInstance($id);
				} else {
					$cache = \Glue\Objects\Cache\File::getInstance($id);
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
					$methods = (isset($route['@attributes']['methods'])) ? explode(',', strtoupper(preg_replace('/\s/', '', $route['@attributes']['methods']))) : NULL;

					if(isset($route['pattern']) && isset($route['target']) && ($methods === NULL || in_array($method, $methods))) {
						$routes['/^' . str_replace('/', '\/', $route['pattern']) . '$/'] = $route['target'];
					}
				}

				if(count($routes) > 0) {
					$uri = preg_replace(array_keys($routes), $routes, $uri);
				}

				$uri = parse_url(urldecode($uri));

				if(isset($uri['query'])) {
					parse_str($uri['query'], $parameters);

					$_GET = array_merge($_GET, $parameters);

					foreach($parameters as $key => $value) {
						if(!isset($_POST[$key]) && !isset($_PUT[$key]) && !isset($_DELETE[$key]) && !isset($_COOKIE[$key])) {
							$_REQUEST[$key] = $value;
						}
					}

					unset($parameters, $key, $value);
				}

				$_REQUEST['Glue']['node'] = \Glue\Helper\Modifier::cleanPath($uri['path']);

				unset($routes, $route, $methods);
			}

			unset($settings, $method, $uri);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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

				$tree[$index]['slug'] = (isset($tree[$index]['slug'])) ? $tree[$index]['slug'] : NULL;

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
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>