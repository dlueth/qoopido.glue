<?php
namespace Glue\Abstracts\Base;

/**
 * Abstract chainable class
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
abstract class Chainable extends \Glue\Abstracts\Base {
	/**
	 * Method to return new instance
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	final public static function getInstance() {
		try {
			$classname = get_called_class();
			$arguments = func_get_args();

			if(count($arguments) > 0) {
				$reflection = new \ReflectionClass($classname);
				$return = $reflection->newInstanceArgs($arguments);

				unset($reflection);

			} else {
				$return = new $classname();
			}

			unset($classname, $arguments);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>