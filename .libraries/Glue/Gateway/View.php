<?php
namespace Glue\Gateway;

/**
 * View gateway
 *
 * @event glue.gateway.view.render.pre(string $handler) > render()
 * @event glue.gateway.view.render.post(string $handler, string &$content) > render()
 * @event glue.gateway.view.render.error(string $handler, \\Exception $exception) > render()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class View extends \Glue\Abstracts\Gateway {
	/**
	 * Property to provide registry
	 *
	 * @object \Glue\Entity\Registry
	 */
	private $registry = NULL;

	/**
	 * Property to store template
	 *
	 * @string
	 */
	private $template = NULL;

	/**
	 * Property to store adapter
	 *
	 * @string
	 */
	private $adapter  = NULL;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize() {
		try {
			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ | \Glue\Entity\Registry::PERMISSION_REGISTER);

			$this->setAdapter(\Glue\Component\Configuration::getInstance()->get(__CLASS__ . '.defaults.adapter'));
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
		if(method_exists($this->registry, $method) === true) {
			return call_user_func_array(array(&$this->registry, $method), (array) $arguments);
		}
	}

	/**
	 * Method to set view adapter
	 *
	 * @param string $adapter
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setAdapter($adapter) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$adapter' => array($adapter, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->adapter = '\Glue\Adapter\View\\' . $adapter;

			if(($mimetype = \Glue\Component\Configuration::getInstance()->get($this->adapter . '.mimetype')) !== NULL) {
				\Glue\Component\Environment::getInstance()->set('mimetype', $mimetype);
			}

			unset($adapter, $result, $mimetype);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
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
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
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
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$header = \Glue\Component\Header::getInstance();
			$header->set('Content-Description', 'File Transfer', true);
			$header->set('Content-Disposition', 'attachment; filename=' . $filename, true);

			unset($filename, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
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
			$classname = $this->adapter;

			$this->dispatcher->notify(new \Glue\Event($this->id . '.render.pre', array($classname)));

			$this->adapter           = new $classname($this);
			$this->adapter->template = ($this->template === NULL) ? \Glue\Component\Environment::getInstance()->get('node') : $this->template;

			$return = $this->adapter->fetch();

			$this->dispatcher->notify(new \Glue\Event($this->id . '.render.post', array($classname, &$return)));

			unset($classname);

			return $return;
		} catch(\Exception $exception) {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.render.error', array($classname, $exception)));
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
