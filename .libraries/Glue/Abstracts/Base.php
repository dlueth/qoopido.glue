<?php
namespace Glue\Abstracts;

/**
 * Abstract base class
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
abstract class Base {
	/**
	 * Property to provide a general class-id
	 */
	protected $id = NULL;

	/**
	 * Property to provide event dispatcher
	 */
	protected $dispatcher = NULL;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	public function __construct() {
		try {
			$this->id         = str_replace('\\', '.', strtolower(get_called_class()));
			$this->dispatcher = \Glue\Dispatcher::getInstance();

			if(method_exists($this, '__initialize')) {
				$arguments = func_get_args();

				if($this->dispatcher !== NULL) {
					$this->dispatcher->notify(new \Glue\Event($this->id . '.initialize.pre'));

					if(count($arguments) > 0) {
						call_user_func_array(array(&$this, '__initialize'), $arguments);
					} else {
						$this->__initialize();
					}

					$this->dispatcher->notify(new \Glue\Event($this->id . '.initialize.post'));
				} else {
					if(count($arguments) > 0) {
						call_user_func_array(array(&$this, '__initialize'), $arguments);
					} else {
						$this->__initialize();
					}
				}

				unset($arguments);
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>