<?php
namespace Glue;

/**
 * Centralized factory handling classes and instances
 *
 * @event glue.factory.load.pre(string $classname, string $instancename) > load()
 * @event glue.factory.load.post(string $classname, string $instancename) > load()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Factory extends \Glue\Abstracts\Base\Singleton {
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
			$this->registry = new \Glue\Entity\Registry($this);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to load, instantiate and register classes
	 *
	 * @param string $classname
	 * @param array $arguments [optional]
	 * @param string $instancename [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function &load($classname, $arguments = NULL, $instancename = NULL) {
		$instancename = ($instancename === NULL) ? $classname : $instancename;

		if(($instance = $this->registry->get($instancename)) !== NULL && is_subclass_of($instance, '\Glue\Abstract\Base\Singleton')) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => $classname), EXCEPTION_CLASS_SINGLETON));
		}

		try {
			$return = false;

			if(class_exists($classname)) {
				$this->dispatcher->notify(new \Glue\Event('glue.factory.load.pre', array($classname, $instancename)));

				if($arguments !== NULL) {
					$reflection = new \ReflectionClass($classname);

					$instance = $reflection->newInstanceArgs(array($arguments));
				} else {
					$instance = new $classname();
				}

				$this->registry->registerReference($instancename, $instance);

				$this->dispatcher->notify(new \Glue\Event('glue.factory.load.post', array($classname, $instancename)));

				$return =  $instance;

				unset($reflection, $instance);
			}

			unset($classname, $arguments, $instancename);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method used to retrieve an instance of a formerly loaded class
	 *
	 * @param string $instancename [optional]
	 * @param string $classname [optional]
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final public function &get($instancename = NULL, $classname = NULL) {
		try {
			$return = false;

			if($instancename !== NULL) {
				if(($instance = $this->registry->get($instancename)) !== NULL) {
					if($classname !== NULL) {
						if($instance instanceof $classname) {
							$return =& $instance;
						}
					} else {
						$return =& $instance;
					}
				}

				unset($instance);
			} else {
				$return =& $this->registry->getReference();
			}

			unset($instancename, $classname);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to check if an instance exists and is of an optional class
	 *
	 * @param string $instancename
	 * @param string $classname [optional]
	 *
	 * @return boolean
	 *
	 * @throw \RuntimeException
	 */
	final public function exists($instancename, $classname = NULL) {
		try {
			$return = false;

			if(($instance = $this->registry->get($instancename)) !== NULL) {
				if($classname !== NULL) {
					if($instance instanceof $classname) {
						$return = true;
					}
				} else {
					$return = true;
				}
			}

			unset($instancename, $classname, $instance);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to manually register an instance
	 *
	 * @param string $instancename
	 * @param object $instance
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final public function &register(&$instance, $instancename = NULL) {
		try {
			$instancename = ($instancename === NULL) ? get_class($instance) : $instancename;

			return $this->registry->registerReference($instancename, $instance);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>