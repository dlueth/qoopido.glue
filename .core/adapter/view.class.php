<?php
namespace Glue\Adapter;

/**
 * View adapter
 *
 * @event glue.adapters.view.render.pre(string $handler) > render()
 * @event glue.adapters.view.render.post(string $handler, string &$content) > render()
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class View extends \Glue\Abstracts\Adapter {
	/**
	 * Property to provide registry
	 *
	 * @object \Glue\Objects\Registry
	 */
	private $registry = NULL;

	/**
	 * Property to store template
	 *
	 * @string
	 */
	private $template = NULL;

	/**
	 * Property to store handler
	 *
	 * @string
	 */
	private $handler  = NULL;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize() {
		try {
			$this->registry = new \Glue\Objects\Registry($this, \Glue\Objects\Registry::PERMISSION_READ | \Glue\Objects\Registry::PERMISSION_REGISTER);

			$this->setHandler(\Glue\Components\Configuration::getInstance()->get(__CLASS__ . '.defaults.handler'));
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
	 * Method to set view handler
	 *
	 * @param string $handler
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setHandler($handler) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$handler' => array($handler, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$this->handler = '\Glue\Handler\View\\' . ucfirst(strtolower($handler));

			if(($mimetype = \Glue\Components\Configuration::getInstance()->get($this->handler . '.mimetype')) !== NULL) {
				\Glue\Components\Environment::getInstance()->set('mimetype', $mimetype);
			}

			unset($handler, $result, $mimetype);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to set template
	 *
	 * @param string $template
	 *
	 * @throw \InvalidArgumentException
	 */
	final public function setTemplate($template) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$template' => array($template, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		$this->template = $template;

		unset($template, $result);
	}

	/**
	 * Method to force a download
	 *
	 * @param string $filename
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setDownload($filename) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$filename' => array($filename, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$header = \Glue\Components\Header::getInstance();
			$header->set('Content-Description', 'File Transfer', true);
			$header->set('Content-Disposition', 'attachment; filename=' . $filename, true);

			unset($filename, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to render the view
	 *
	 * @return string
	 *
	 * @throw \RuntimeException
	 */
	final public function render() {
		try {
			$classname = $this->handler;

			$this->dispatcher->notify(new \Glue\Event($this->id . '.render.pre', array($classname)));

			$this->handler           = new $classname($this);
			$this->handler->template = ($this->template === NULL) ? \Glue\Components\Environment::getInstance()->get('slug') : $this->template;

			$return = $this->handler->fetch();

			$this->dispatcher->notify(new \Glue\Event($this->id . '.render.post', array($classname, &$return)));

			unset($classname);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>