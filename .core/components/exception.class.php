<?php
namespace Glue\Components;

/**
 * Component for exception handling
 *
 * @event glue.exception(Exception $exception) > handleException()
 * @event glue.error.all(ErrorException $exception) > handleError()
 * @event glue.error.error(ErrorException $exception) > handleError()
 * @event glue.error.warning(ErrorException $exception) > handleError()
 * @event glue.error.parse(ErrorException $exception) > handleError()
 * @event glue.error.notice(ErrorException $exception) > handleError()
 * @event glue.error.strict(ErrorException $exception) > handleError()
 * @event glue.error.deprecated(ErrorException $exception) > handleError()
 * @event glue.error.core.error(ErrorException $exception) > handleError()
 * @event glue.error.core.warning(ErrorException $exception) > handleError()
 * @event glue.error.compile.error(ErrorException $exception) > handleError()
 * @event glue.error.compile.warning(ErrorException $exception) > handleError()
 * @event glue.error.user.error(ErrorException $exception) > handleError()
 * @event glue.error.user.warning(ErrorException $exception) > handleError()
 * @event glue.error.user.notice(ErrorException $exception) > handleError()
 * @event glue.error.user.deprecated(ErrorException $exception) > handleError()
 * @event glue.error.recoverable.error(ErrorException $exception) > handleError()
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Exception extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Property for error types
	 *
	 * @array
	 */
	private $types = array();

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize() {
		try {
			$this->types = array(
				E_ALL               => 'all',
				E_ERROR             => 'error',
				E_WARNING           => 'warning',
				E_PARSE             => 'parse',
				E_NOTICE            => 'notice',
				E_STRICT            => 'strict',
				E_DEPRECATED        => 'deprecated',
				E_CORE_ERROR        => 'core.error',
				E_CORE_WARNING      => 'core.warning',
				E_COMPILE_ERROR     => 'compile.error',
				E_COMPILE_WARNING   => 'compile.warning',
				E_USER_ERROR        => 'user.error',
				E_USER_WARNING      => 'user.warning',
				E_USER_NOTICE       => 'user.notice',
				E_USER_DEPRECATED   => 'user.deprecated',
				E_RECOVERABLE_ERROR => 'recoverable.error'
			);

			set_exception_handler(array(&$this, 'handleException'));
			set_error_handler(array(&$this, 'handleError'));
			error_reporting(E_ALL);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to handle errors
	 *
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 */
	public function handleError($errno, $errstr, $errfile, $errline) {
		if(error_reporting() == 0 || (ini_get('error_reporting') & $errno) !== $errno) {
			return;
		}

		$this->dispatcher->notify(new \Glue\Event('glue.error.' . $this->types[$errno], array(new \ErrorException($errstr, 0, $errno, $errfile, $errline))));
	}

	/**
	 * Method to handle exceptions
	 *
	 * @param object $exception
	 */
	public function handleException($exception) {
		$this->dispatcher->notify(new \Glue\Event('glue.exception', array($exception)));
	}
}
?>