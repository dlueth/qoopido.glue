<?php
namespace Glue\Abstracts\Base;

/**
 * Abstract singleton class
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
abstract class Singleton extends \Glue\Abstracts\Base {
	/**
	 * Property to store instances
	 *
	 * @array
	 */
	private static $instance = array();

	/**
	 * Class constructor
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function __construct() {
		try {
			$classname = get_called_class();
			$arguments = func_get_args();

			if(isset(self::$instance[$classname])) {
				throw new \LogicException(\Glue\Helper\General::replace(array('class' => $classname), GLUE_EXCEPTION_CLASS_SINGLETON));
			} else {
				self::$instance[$classname] =& $this;
			}

			if(count($arguments) > 0) {
				call_user_func_array(array('parent', '__construct'), $arguments);
			} else {
				parent::__construct();
			}

			unset($classname, $arguments);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Magic method to suppress cloning
	 *
	 * @throw \LogicException
	 */
	final public function __clone() {
		throw new \LogicException(\Glue\Helper\General::replace(array('class' => get_called_class()), GLUE_EXCEPTION_CLASS_SINGLETON));
	}

	/**
	 * Method to create, store and retrieve instance
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final public static function &getInstance() {
		try {
			$classname = get_called_class();

			if(!isset(self::$instance[$classname])) {
				$arguments = func_get_args();

				if(count($arguments) > 0) {
					$reflection                 = new \ReflectionClass($classname);
					self::$instance[$classname] = $reflection->newInstanceArgs($arguments);

					unset($reflection);
				} else {
					self::$instance[$classname] = new $classname();
				}

				\Glue\Factory::getInstance()->register(self::$instance[$classname]);
			}

			return self::$instance[$classname];
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
