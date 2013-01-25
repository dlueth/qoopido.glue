<?php
namespace Glue\Module {
	/**
	 * Curl module
	 *
	 * credits https://github.com/jmathai/php-multi-curl
	 *
	 * @require PHP "CURL" extension
	 *
	 * @author Dirk Lüth <info@qoopido.com>
	 */
	class Curl extends \Glue\Abstracts\Base\Singleton {
		/**
		 * Static, once only constructor
		 *
		 * @throw \LogicException
		 */
		public static function __once() {
			if(extension_loaded('curl') !== true) {
				throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'PDO'), EXCEPTION_EXTENSION_MISSING));
			}
		}

		/**
		 * Property to store curl handle
		 */
		protected $configuration = NULL;

		/**
		 * Property to store status
		 */
		protected $status    = NULL;

		/**
		 * Property to store running
		 */
		protected $running   = NULL;

		/**
		 * Property to store delay
		 */
		protected $delay     = 1.1;

		/**
		 * Property to store requests
		 */
		protected $requests  = array();

		/**
		 * Property to store responses
		 */
		protected $responses = array();

		/**
		 * Class constructor
		 *
		 * @throw \RuntimeException
		 */
		protected function __initialize() {
			try {
				$this->curl = curl_multi_init();
			} catch(\Exception $exception) {
				throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
			}
		}

		/**
		 * Method to add a curl request
		 *
		 * @param resource $curl
		 *
		 * @return int
		 *
		 * @throw \InvalidArgumentException
		 * @throw \RuntimeException
		 */
		public function add($curl) {
			if(($result = \Glue\Helper\validator::batch(array(
				'$curl' => array($curl, 'isResource')
			))) !== true) {
				throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
			}

			try {
				curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, 'callback'));

				$key    = (string) $curl;
				$status = curl_multi_add_handle($this->curl, $curl);

				$this->requests[$key] = $curl;

				if($status === CURLM_OK || $status === CURLM_CALL_MULTI_PERFORM) {
					do {
						$this->status = curl_multi_exec($this->curl, $this->running);
					} while($this->status === CURLM_CALL_MULTI_PERFORM);

					return new \Glue\Entity\Curlrequest($key, $this);
				}

				return $status;
			} catch(\Exception $exception) {
				throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
			}
		}

		/**
		 * Method to get the result of a curl request
		 *
		 * @param string $key
		 *
		 * @return imxed
		 *
		 * @throw \InvalidArgumentException
		 * @throw \RuntimeException
		 */
		public function get($key) {
			if(($result = \Glue\Helper\validator::batch(array(
				'$key' => array($key, 'isString')
			))) !== true) {
				throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
			}

			try {
				if(isset($this->responses[$key])) {
					return $this->responses[$key];
				}

				$sleepInner = $sleepOuter = 1;

				while($this->running && ($this->status == CURLM_OK || $this->status == CURLM_CALL_MULTI_PERFORM)) {
					usleep($sleepOuter);

					$sleepOuter *= $this->delay;

					$select = curl_multi_select($this->curl, 0);

					if($select > 0) {
						do {
							$this->status = curl_multi_exec($this->curl, $this->running);

							usleep($sleepInner);

							$sleepInner *= $this->delay;
						} while($this->status === CURLM_CALL_MULTI_PERFORM);

						$sleepInner = 1;
					}

					$this->storeResponse();

					if(isset($this->responses[$key]['response'])) {
						return $this->responses[$key];
					}
				}

				return NULL;
			} catch(\Exception $exception) {
				throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
			}
		}

		protected function callback($curl, $header) {
			preg_match_all('/(?P<key>.+?):(?P<value>.+)/', trim($header), $matches, PREG_SET_ORDER);

			if(count($matches) > 0) {
				$this->responses[(string) $curl]['header'][$matches[0]['key']] = preg_replace('/^\W+/', '',$matches[0]['value']);

				unset($matches);
			}

			return strlen($header);
		}

		protected function storeResponse() {
			while($done = curl_multi_info_read($this->curl)) {
				$key = (string) $done['handle'];

				$this->responses[$key]['info']     = curl_getinfo($done['handle']);
				$this->responses[$key]['response'] = curl_multi_getcontent($done['handle']);

				curl_multi_remove_handle($this->curl, $done['handle']);
				curl_close($done['handle']);
			}
		}
	}
}

/**
 * Object for curl request handling
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
namespace Glue\Entity {
	class Curlrequest {
		protected $key    = NULL;
		protected $module = NULL;

		/**
		 * Class constructor
		 *
		 * @param string $key
		 * @param object $module
		 *
		 * @throw \RuntimeException
		 */
		public function __construct($key, \Glue\Module\Curl &$module){
			$this->key    = $key;
			$this->module = $module;
		}

		/**
		 * Magic method to retrieve restricted properties
		 *
		 * @param string $property
		 *
		 * @return mixed
		 */
		function __get($property) {
			$response = $this->module->get($this->key);

			return (isset($response[$property])) ? $response[$property] : false;
		}

		/**
		 * Magic method for checking the existance of unknown or restricted properties
		 *
		 * @param string $property
		 *
		 * @return bool
		 */
		function __isset($property){
			$value = self::__get($property);

			return empty($value);
		}
	}
}
?>