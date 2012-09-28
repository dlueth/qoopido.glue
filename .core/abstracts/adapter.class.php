<?php
namespace Glue\Abstracts;

/**
 * Abstract handler class
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
abstract class Handler extends \Glue\Abstracts\Base {
	/**
	 * Property to provide adapter
	 */
	protected $adapter = NULL;

	/**
	 * Property to provide environment
	 */
	protected static $environment = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$environment = \Glue\Components\Environment::getInstance();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @param object $adapter
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function __construct(\Glue\Abstracts\Adapter &$adapter) {
		try {
			$arguments = func_get_args();

			$this->adapter =& $adapter;

			if(count($arguments) > 0) {
				call_user_func_array(array('parent', '__construct'), $arguments);
			} else {
				parent::__construct();
			}

			unset($adapter, $arguments);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>