<?php
namespace Glue\Helper;

/**
  * General helper class to generate RFC UUIDs
  *
  * @author Dirk Lüth <info@qoopido.de>
  */
class Uuid {
	const NS_DNS     = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
	const NS_URL     = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
	const NS_ISO_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
	const NS_X500_DN = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

	/**
	 * Closure to generate a random number
	 */
	protected static $_closureGetRandom = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			if(function_exists('posix_getpid') === true) {
				self::$_closureGetRandom = function($min, $max) {
					mt_srand(crc32((double) (microtime() ^ posix_getpid())));
					return mt_rand($min, $max);
				};
			} else {
				self::$_closureGetRandom = function($min, $max) {
					mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
					return mt_rand($min, $max);
				};
			}

		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to generate a UUID, version 3 (Name-based, md5)
	 *
	 * @param string $namespace
	 * @param string $name
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function v3($namespace, $name) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$namespace' => array($namespace, 'isUUID')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$nhex = str_replace(array('-','{','}'), '', $namespace);
			$nstr = '';

			for($i = 0; $i < strlen($nhex); $i+=2) {
				$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
			}

			$hash = md5($nstr . $name);

			$return = sprintf('%08s-%04s-%04x-%04x-%12s',
				substr($hash, 0, 8),
				substr($hash, 8, 4),
				(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
				(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
				substr($hash, 20, 12)
			);

			unset($namespace, $name, $nhex, $nstr, $hash);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to generate a UUID, version 4 (Pseudo-random)
	 *
	 * @return string
	 *
	 * @throw \RuntimeException
	 */
	public static function v4() {
		try {
			$closure = self::$_closureGetRandom;

			$return = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				$closure(0, 0xffff), $closure(0, 0xffff),
				$closure(0, 0xffff),
				$closure(0, 0x0fff) | 0x4000,
				$closure(0, 0x3fff) | 0x8000,
				$closure(0, 0xffff), $closure(0, 0xffff), $closure(0, 0xffff)
			);

			unset($closure);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to generate a UUID, version 5 (Name-based, sha1)
	 *
	 * @param string $namespace
	 * @param string $name
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function v5($namespace, $name) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$namespace' => array($namespace, 'isUUID')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$nhex = str_replace(array('-','{','}'), '', $namespace);
			$nstr = '';

			for($i = 0; $i < strlen($nhex); $i+=2) {
				$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
			}

			$hash = sha1($nstr . $name);

			$return = sprintf('%08s-%04s-%04x-%04x-%12s',
				substr($hash, 0, 8),
				substr($hash, 8, 4),
				(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
				(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
				substr($hash, 20, 12)
			);

			unset($namespace, $name, $nhex, $nstr, $hash);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>