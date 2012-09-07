<?php
namespace Glue\Helper;

/**
  * General helper for urls
  *
  * @author Dirk Lüth <dirk@qoopido.de>
  */
class Url {
	/**
	 * Property to store environment
	 */
	protected static $environment = NULL;

	/**
	 * Property to store url
	 */
	protected static $url = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$environment = \Glue\Components\Environment::getInstance();
			self::$url         = \Glue\Components\Url::getInstance();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to retrieve an url
	 *
	 * @param string $url
	 * @param string $scope [optional]
	 * @param array $parameters [optional]
	 * @param string $anchor [optional]
	 * @param string $separator [optional]
	 *
	 * @return string
	 *
	 * @throw \RuntimeException
	 */
	public static function make($url, $scope = 'local', array $parameters = array(), $anchor = false, $separator = '&amp;') {
		if(($result = \Glue\Helper\validator::batch(array(
			'$url'        => array($url, 'isString'),
			'$scope'      => array($scope, 'isString', array('matchesPattern', array('^global|local$'))),
			'$parameters' => array($parameters, 'isArray'),
			'$separator'  => array($separator, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$url        = \Glue\Helper\Modifier::cleanPath($url);
			$anchor     = (is_string($anchor) && !empty($anchor)) ? '#' . rawurlencode($anchor) : false;
			$components = parse_url($url);
			$type       = (!isset($components['host']) || preg_match('/^' . preg_quote(self::$environment->get('url.absolute'), '/') .'/', $components['host'])) ? 'internal' : 'external';

			unset($components);

			if($type === 'internal') {
				$switches = self::$url->get('switches');
				$switches = (is_array($switches)) ? array_reverse($switches) : NULL;

				$url = preg_replace('/^' . preg_quote(self::$environment->get('path.global'), '/') . '/', '', $url);
				$url = preg_replace('/^' . preg_quote(self::$environment->get('url.absolute'), '/') . '/', '', $url);
				$url = preg_replace('/^\/?' . preg_quote(self::$environment->get('site'), '/') . '/', '', $url);

				switch($scope) {
					case 'local':
						$url = (!is_file(self::$environment->get('path.local') . '/' . $url)) ? $url . '/' : $url;
						$url = (preg_match('/^\//', $url)) ? self::$environment->get('site') . $url : self::$environment->get('site') . '/' . $url;
						break;
					default:
						$url = (!is_file(self::$environment->get('path.global') . '/' . $url)) ? $url . '/' : $url;
						$url = preg_replace('/^\//', '', $url);

						if($switches !== NULL) {
							foreach($switches as $switch => $value) {
								if(isset($parameters[$switch])) {
									$value = $parameters[$switch];

									unset($parameters[$switch]);
								}

								if($value !== NULL) {
									$url = '!' . $value . '/' . $url;
								}
							}
						}
						break;
				}

				if(self::$environment->get('urlrewriting') !== true) {
					$parameters = array_merge(array('Glue' => $url), $parameters);
					$url        = false;
				}

				unset($switches);
			}

			if(is_array($parameters) && count($parameters) > 0) {
				$parameters = '?' . http_build_query($parameters, '', $separator);
			} else {
				$parameters = false;
			}

			$return = ($parameters != false) ? $url . $parameters . $anchor : $url . $anchor;

			unset($url, $scope, $parameters, $anchor, $separator, $result, $components, $type);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to redirect to an url
	 *
	 * @param string $url
	 * @param string $scope [optional]
	 * @param array $parameters [optional]
	 * @param string $anchor [optional]
	 *
	 * @throw \RuntimeException
	 */
	public static function redirect($url, $scope = 'auto', $parameters = array(), $anchor = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$url'        => array($url, 'isString'),
			'$scope'      => array($scope, 'isString', array('matchesPattern', array('^global|local$'))),
			'$parameters' => array($parameters, 'isArray')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$url      = str_replace(self::$environment->get('url.absolute'), '', $url);
			$temp     = parse_url($url);
			$internal = (!isset($temp['host'])) ? true : false;
			$url      = self::make($url, $scope, $parameters, $anchor, '&');
			$url      = ($internal == true) ? self::$environment->get('url.relative') . $url : $url;

			header('Location: ' . $url);
			header('Connection: close');

			unset($url, $scope, $parameters, $anchor, $result, $temp, $internal);

			exit;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>