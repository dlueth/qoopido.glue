<?php
namespace Glue\Component;

/**
 * Component for header abstraction
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Header extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to provide registry
	 *
	 * @object \Glue\Entity\Registry
	 */
	private $registry = NULL;
	
	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_WRITE);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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
		$return = NULL;

		switch($method) {
			case 'get':
				$return = call_user_func_array(array(&$this->registry, 'get'), (array) $arguments);
				break;
			case 'set':
				$return = false;

				if(count($this->registry->splitNode($arguments[0])) === 1) {
					if($this->registry->exists($arguments[0]) === false) {
						call_user_func_array(array(&$this->registry, 'register'), array($arguments[0], array()));
					}

					if(is_scalar($arguments[1])) {
						$arguments[1] = array($arguments[1]);
					}

					if(!isset($arguments[2]) || $arguments[2] !== false) {
						$arguments[1] = array_merge((array) $this->registry->get($arguments[0]), (array) $arguments[1]);
					}

					$return = call_user_func_array(array(&$this->registry, 'set'), $arguments);
				}
				break;
		}

		unset($method, $arguments);

		return $return;
	}
}
