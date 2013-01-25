<?php
namespace Glue\Listener;

/**
 * Exception listener
 *
 * @listen glue.exception > handleException()
 * @listen glue.error > handleException()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Exception extends \Glue\Abstracts\Base  {
	/**
	 * Property storing path information
	 */
	protected $path = NULL;

	/**
	 * Property storing regular expression to make path relative
	 */
	protected $expression = NULL;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize(array $path) {
		try {
			$this->path       =& $path;
			$this->expression = '/^' . preg_quote($path['global'], '/') . '/i';

			$this->dispatcher->addListener(array(&$this, 'handleException'), 'glue.error');
			$this->dispatcher->addListener(array(&$this, 'handleException'), 'glue.exception');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to handle exceptions
	 *
	 * @param object $event
	 * @param object $exception
	 */
	public function handleException(\Glue\Event $event, $exception) {
		if(\Glue\Factory::getInstance()->exists('\Glue\Component\Environment') === true) {
			$configuration = \Glue\Helper\General::merge(array('display' => false, 'file' => true), (array) \Glue\Component\Configuration::getInstance()->get(__CLASS__));
		} else {
			$configuration = array('display' => true, 'file' => true);
		}

		$uuid    = \Glue\Helper\Uuid::v4();
		$message  = '[' . date('Y-m-d H:i:s') . '] ' . $uuid . chr(10);
		$message .= '                      > ' . get_class($exception) . ' in ' . preg_replace($this->expression, '', $exception->getFile()) . ' on line ' . $exception->getLine() . ' (' . $exception->getMessage() . ')' . chr(10);

		while(($exception = $exception->getPrevious()) !== NULL) {
			$message .= '                      > ' . get_class($exception) . ' in ' . preg_replace($this->expression, '', $exception->getFile()) . ' on line ' . $exception->getLine() . ' (' . $exception->getMessage() . ')' . chr(10);
		}

		if($configuration['file'] === true) {
			$logfile = ($this->path['local'] !== NULL) ? $this->path['local'] . '/.logfiles/' . date('Ymd') . '.exception.log' : $this->path['global'] . '/.logfiles/' . date('Ymd') . '.exception.log';

			\Glue\Helper\Filesystem::updateFile($logfile, $message, true, true);

			unset($logfile);
		}

		if($configuration['display'] === true) {
			echo $message;
		} else {
			echo 'Unexpected error ' . $uuid . ' occured';
		}

		unset($configuration, $message);
	}
}
?>