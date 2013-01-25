<?php
namespace Glue\Abstracts;

/**
 * Abstract adapter class
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
abstract class Adapter extends \Glue\Abstracts\Base {
	/**
	 * Property to provide gateway
	 */
	protected $gateway = NULL;

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
			self::$environment = \Glue\Component\Environment::getInstance();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @param object $gateway
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function __construct(\Glue\Abstracts\Gateway &$gateway) {
		try {
			$arguments = func_get_args();

			$this->gateway =& $gateway;

			if(count($arguments) > 0) {
				call_user_func_array(array('parent', '__construct'), $arguments);
			} else {
				parent::__construct();
			}

			unset($gateway, $arguments);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>