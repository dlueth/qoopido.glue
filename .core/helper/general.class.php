<?php
namespace Glue\Helper;

/**
  * General helper class
  *
  * @author Dirk Lüth <info@qoopido.de>
  */
class General {
	/**
	 * Property to store support for posix_getpid
	 */
	protected static $getpid = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$getpid = function_exists('posix_getpid');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to load a single configuration file
	 *
	 * @param mixed $files
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function loadConfiguration($files) {
		$files = (is_string($files)) ? (array) $files : $files;

		if(($result = \Glue\Helper\validator::batch(array(
			'@$files' => array($files, 'isString', 'isPathValid')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = array();

			foreach($files as $file) {
				if(is_file($file) && ($data = @simplexml_load_file($file, NULL, LIBXML_NOCDATA)) !== false) {
					$data   = json_decode(preg_replace('/:"(true|false|null)"/i', ':\1', json_encode($data)), true);
					$return = self::merge($return, $data);
				}

				unset($data);
			}

			$return = (count($return) > 0) ? $return : false;

			unset($files, $result, $file);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to replace placeholders in strings
	 *
	 * @param array $replacements
	 * @param string $string
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function replace(array $replacements, $string) {
		if(($result = \Glue\Helper\validator::batch(array(
			'@$replacements' => array($replacements, 'isString'),
			'$string'        => array($string, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$keys   = array_map(function($value) { return '{' . $value . '}'; }, array_keys($replacements));
			$values = array_values($replacements);
			$return = preg_replace('/\s{2,}/', ' ', strtr($string, array_combine($keys, $values)));

			unset($replacements, $string, $result, $keys, $values);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to recursively merge 2 or more arrays
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function merge() {
		$arguments = func_get_args();

		if(($result = \Glue\Helper\validator::batch(array(
			'@' => array($arguments, 'isArray')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = array_shift($arguments);

			foreach($arguments as $argument) {
				foreach($argument as $key => $value) {
					$type = gettype($value);

					switch($type) {
						case 'array':
							if(isset($return[$key]) && is_array($return[$key])) {
								$return[$key] = self::merge($return[$key], $argument[$key]);
							} else {
								$return[$key] = $argument[$key];
							}
							break;
						default:
							$return[$key] = $argument[$key];
							break;
					}

					unset($type);
				}

				unset($key, $value);
			}

			unset($arguments, $result, $argument);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>