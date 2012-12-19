<?php
namespace Glue\Component;

/**
 * Component for client/visitor abstraction
 *
 * @listen glue.component.environment.process.pre > onPreProcess()
 * @listen glue.gateway.view.render.pre > onPreRender()
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
final class Client extends \Glue\Abstracts\Base\Singleton {
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
			\Glue\Component\Environment::getInstance()->register('modifier.client', $this->modifier);
		}
	}

	/**
	 * Event listener
	 */
	final public function onPreRender() {
		\Glue\Factory::getInstance()->get('\Glue\Gateway\View')->register('client', $this->registry->get());
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$this->dispatcher->addListener(array(&$this, 'onPreProcess'), 'glue.component.environment.process.pre');
			$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.gateway.view.render.pre');

			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ);

			$modifier                       = \Glue\Component\Configuration::getInstance()->get(__CLASS__ . '.@attributes.modifier');
			$data                           = array();
			$data['reload']                 = (isset($_SERVER['HTTP_CACHE_CONTROL']) && preg_match('/max-age=0|no-cache/i', $_SERVER['HTTP_CACHE_CONTROL'])) ? true : false;
			$data['ip']                     = $this->_getRealAddress();
			$data['private']                = (bool) preg_match('/^127\.0\.0\.\d{1,3}$/', $data['ip']) || !filter_var($data['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE);
			$data['useragent']              = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
			$data['track']                  = (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == '1') ? false : (isset($_SERVER['HTTP_DNT'])) ? true : NULL;
			$data['accept']                 = array();
			$data['accept']['language']     = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : NULL;
			$data['accept']['encoding']     = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : NULL;
			$data['accept']['characterset'] = (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : NULL;

			if($modifier !== NULL) {
				$modifier = array_flip(explode(',', preg_replace('/\s/', '', $modifier)));

				foreach($modifier as $key => $value) {
					if(isset($data[$key])) {
						$modifier[$key] = $data[$key];
					} else {
						unset($modifier[$key]);
					}
				}

				if(count($modifier) > 0) {
					$this->modifier = $modifier;
				}
			}

			$this->registry->set(NULL, $data);

			unset($data);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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

	/**
	 * Method to retrieve a clients/visitors real IP-address
	 *
	 * @return string
	 *
	 * @throw \RuntimeException
	 */
	private function _getRealAddress() {
		try {
			$return = NULL;

			if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
				$return = $_SERVER['HTTP_CLIENT_IP'];
			} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				list($return) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			} elseif(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
				$return = $_SERVER['REMOTE_ADDR'];
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>