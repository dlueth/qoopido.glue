<?php
namespace Glue\Helper;

/**
 * Helper for general modifier
 *
 * @require PHP "MBSTRING" extension for convertCharset() and transliterate()
 * @require PHP "ICONV" extension for transliterate()
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Modifier {
	/**
	 * Property to store diacritics
	 */
	protected static $diacritics  = array(
		'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A','Ä'=>'A','Æ'=>'AE',
		'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','å'=>'a','ä'=>'a','æ'=>'ae',
		'Þ'=>'B','þ'=>'b','Č'=>'C','Ć'=>'C','Ç'=>'C','č'=>'c','ć'=>'c',
		'ç'=>'c','Ď'=>'D','ð'=>'d','ď'=>'d','Đ'=>'Dj','đ'=>'dj','È'=>'E',
		'É'=>'E','Ê'=>'E','Ë'=>'E','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
		'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','ì'=>'i','í'=>'i','î'=>'i',
		'ï'=>'i','Ľ'=>'L','ľ'=>'l','Ñ'=>'N','Ň'=>'N','ñ'=>'n','ň'=>'n',
		'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ø'=>'O','Ö'=>'O','Œ'=>'OE',
		'ð'=>'o','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','œ'=>'oe',
		'ø'=>'o','Ŕ'=>'R','Ř'=>'R','ŕ'=>'r','ř'=>'r','Š'=>'S','š'=>'s',
		'ß'=>'ss','Ť'=>'T','ť'=>'t','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
		'Ů'=>'U','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ů'=>'u','Ý'=>'Y',
		'Ÿ'=>'Y','ý'=>'y','ý'=>'y','ÿ'=>'y','Ž'=>'Z','ž'=>'z'
	);

	/**
	 * Property to store support for mbstring
	 */
	protected static $mbstring = NULL;

	/**
	 * Property to store support for iconv
	 */
	protected static $iconv = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$mbstring = extension_loaded('mbstring');
			self::$iconv    = extension_loaded('iconv');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to convert a given value to float
	 *
	 * @param mixed $value
	 *
	 * @return float
	 */
	public static function float($value) {
		return (float) $value;
	}

	/**
	 * Method to convert a given value to int
	 *
	 * @param mixed $value
	 *
	 * @return int
	 */
	public static function int($value) {
		return (int) $value;
	}

	/**
	 * Method to trim a given string
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 */
	public static function trim($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		unset($result);

		return trim($value);
	}

	/**
	 * Method to convert a given string to be uppercase
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 */
	public static function uc($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		unset($result);

		return strtoupper($value);
	}

	/**
	 * Method to convert the first letter of a given string to be uppercase
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 */
	public static function ucFirst($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		unset($result);

		return ucfirst($value);
	}

	/**
	 * Method to convert the first letter of all words to be uppercase
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 */
	public static function ucWords($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		unset($result);

		return ucwords($value);
	}

	/**
	 * Method to convert a given string to a different characterset
	 *
	 * @param string $value
	 * @param string $characterset [optional]
	 *
	 * @return string
	 *
	 * @throw \LogicException
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function convertCharset($value, $characterset = NULL) {
		if(self::$mbstring !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'MBSTRING'), EXCEPTION_EXTENSION_MISSING));
		}

		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			if($characterset === NULL) {
				$characterset = \Glue\Components\Environment::getInstance()->get('characterset');
			}

			$return =  (mb_check_encoding($value, $characterset) === true) ? $value : mb_convert_encoding($value, $characterset);

			unset($value, $characterset, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to sluggify a given string
	 *
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function sluggify($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return =  trim(strtolower(preg_replace('/([^\w]|-)+/', '-', trim(strtr(str_replace('\'', '', trim($value)), self::$diacritics)))));

			unset($value, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to transliterate a given string
	 *
	 * @param string $value
	 * @param string $characterset [optional]
	 *
	 * @return string
	 *
	 * @throw \LogicException
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function transliterate($value, $characterset = NULL) {
		if(self::$mbstring !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'MBSTRING'), EXCEPTION_EXTENSION_MISSING));
		}

		if(self::$iconv !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'ICONV'), EXCEPTION_EXTENSION_MISSING));
		}

		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			if($characterset === NULL) {
				$characterset = \Glue\Components\Environment::getInstance()->get('characterset');
			}

			$return = iconv(mb_detect_encoding($value, NULL, true), $characterset . '//IGNORE//TRANSLIT', trim($value));

			unset($value, $characterset, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to sanitize a given string
	 *
	 * @param string $value
	 *
	 * @return string
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function sanitize($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value'   => array($value, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$value = preg_replace('/[\x00-\x1f\?*:";|\/°^!§$%&\\()=´`+#\':,<>]/', '', trim($value));
			$value = preg_replace('/^(?:PRN|AUX|CLOCK\$|NUL|CON|COM\d|LPT\d)(?:\.*)(.*)/', '\1', $value);

			unset($result);

			return $value;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to clean/sanitize a path
	 *
	 * @param string $path
	 * @param bool $leading [optional]
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 */
	public static function cleanPath($path, $leading = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$path'       => array($path, 'isString'),
			'$leading' => array($leading, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// trim
				$path = trim($path);

			// replace backslashes with slashes
				$path = str_replace('\\', '/', $path);

			// replace repeated slashes with single slash
				$path = preg_replace('/\/(\/+)/', '/', $path);

			//	remove neutral ./
				$path = preg_replace('/(\/\.(?=\/))|(\/\.$)/', '', $path);

			// canonicalize
				while(($collapsed = preg_replace('/(?:\/|)[^\/]*\/\.\./', '', $path, 1)) !== $path) {
					$path = $collapsed;
				}

			// remove leading slash
				if($leading === true) {
					$path = preg_replace('/^\/+/si', '', $path);
				}


			unset($leading, $result, $collapsed);

			// remove trailing slash and return path
				return preg_replace('/^(.*?)\/{0,1}$/si', '$1', $path);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>