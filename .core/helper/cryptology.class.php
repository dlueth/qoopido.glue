<?php
namespace Glue\Helper;

/**
 * Helper for encryption and decryption
 *
 * @require PHP "MCRYPT" extension
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
class Cryptology {
	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('mcrypt') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'MCRYPT'), EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Method to encrypt a string
	 *
	 * @param string $algorithm [optional]
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function encrypt($algorithm = '3des', $key, $value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$algorithm' => array($algorithm, 'isString', array('matchesPattern', array('^des|3des|aes|gost$'))),
			'$key' => array($key, 'isString', 'isNotEmpty'),
			'$value' => array($value, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$cipher = self::_getCipher($algorithm);
			$vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($cipher), MCRYPT_DEV_RANDOM);
			$key    = substr($key, 0, mcrypt_enc_get_key_size($cipher));

			mcrypt_generic_init($cipher, $key, $vector);

			$return = mcrypt_generic($cipher, $value);

			mcrypt_generic_deinit($cipher);
			mcrypt_module_close($cipher);

			unset($algorithm, $key, $value, $result, $cipher, $vector);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to decrypt a string
	 *
	 * @param string $algorithm [optional]
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function decrypt($algorithm = '3des', $key, $value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$algorithm' => array($algorithm, 'isString', array('matchesPattern', array('^des|3des|aes|gost$'))),
			'$key' => array($key, 'isString', 'isNotEmpty'),
			'$value' => array($value, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$cipher = self::_getCipher($algorithm);
			$vector = mcrypt_create_iv(mcrypt_enc_get_iv_size($cipher), MCRYPT_DEV_RANDOM);
			$key    = substr($key, 0, mcrypt_enc_get_key_size($cipher));

			mcrypt_generic_init($cipher, $key, $vector);

			$return = mdecrypt_generic($cipher, $value);

			mcrypt_generic_deinit($cipher);
			mcrypt_module_close($cipher);

			unset($algorithm, $key, $value, $result, $cipher, $vector);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get cipher for an algorithm
	 *
	 * @param string $algorithm
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 */
	private static function _getCipher($algorithm) {
		try {
			switch($algorithm) {
				case 'des':
					return mcrypt_module_open(MCRYPT_DES, '', 'ecb', '');
					break;
				case '3des':
					return mcrypt_module_open(MCRYPT_3DES, '', 'ecb', '');
					break;
				case 'aes':
					return mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'ecb', '');
					break;
				case 'gost':
					return mcrypt_module_open(MCRYPT_GOST, '', 'ecb', '');
					break;
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>