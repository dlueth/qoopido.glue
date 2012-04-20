<?php
namespace Glue\Helper;

/**
 * Helper for YUI compressor
 *
 * @require PHP "CURL" extension or "allow_url_fopen = On" in php.ini
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Yui {
	/**
	 * Property to store presence of CURL
	 */
	protected static $curl     = NULL;

	/**
	 * Property to store status of allow_url_fopen
	 */
	protected static $urlfopen = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$curl      = extension_loaded('curl');
			self::$urlfopen  = (bool) ini_get('allow_url_fopen');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to compress a string via YUI
	 *
	 * @param string $type
	 * @param string $source
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function compress($type, $source) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$type'        => array($type, 'isString', array('matchesPattern', array('^css|js$'))),
			'$source'      => array($source, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$type   = strtolower($type);
			$source = trim($source);

			if(empty($source)) {
				return false;
			}

			$url  = 'http://refresh-sf.com/yui/';
			$type = strtoupper($type);
			$data = http_build_query(array(
				'compresstext' => $source,
				'type'         => $type,
				'redirect'     => '1'
			));

			if(self::$curl === true) {
				$curl    = \Glue\Modules\Curl::getInstance();
				$request = curl_init($url);

				curl_setopt_array($request, array(
					CURLOPT_POST           => true,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HEADER         => false,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_POSTFIELDS     => $data
				));

				$request = $curl->add($request);

				$return = $request->response;

				unset($curl, $request);
			} elseif(self::$urlfopen === true) {
				$options = array('http' => array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => $data
				));

				$context = stream_context_create($options);

				$return = @file_get_contents($url, false, $context);

				unset($options, $context);
			}

			unset($type, $source, $result, $url, $data);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>