<?php
namespace Glue\Component;

/**
 * Component for request abstraction
 *
 * @require PHP "SIMPLEXML" extension
 *
 * @listen glue.gateways.view.render.pre > onPreRender()
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Request extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to provide registry
	 *
	 * @object \Glue\Objects\Registry
	 */
	private $registry = NULL;

	/**
	 * Event listener
	 */
	final public function onPreRender() {
		\Glue\Factory::getInstance()->get('\Glue\Gateways\View')->register('request', $this->registry->get());
	}

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('simplexml') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'SIMPLEXML'), EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			global $_PUT, $_DELETE;

			//echo '<pre>' . print_r($_REQUEST, true) . '</pre>';
			//die();

			$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.gateways.view.render.pre');

			$this->registry = new \Glue\Objects\Registry($this, \Glue\Objects\Registry::PERMISSION_WRITE);

			$method   = (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) ? strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) : strtoupper($_SERVER['REQUEST_METHOD']);

			$_REQUEST = $this->_clean($_REQUEST);
			$_GET     = $this->_clean($_GET);
			$_POST    = $this->_clean($_POST);
			$_COOKIE  = $this->_clean($_COOKIE);
			$_PUT     = array();
			$_DELETE  = array();

			switch($method) {
				case 'PUT':
					parse_str(@file_get_contents('php://input'), $_PUT);
					$_PUT = $this->_clean($_PUT);
					break;
				case 'DELETE':
					parse_str(@file_get_contents('php://input'), $_DELETE);
					$_DELETE = $this->_clean($_DELETE);
					break;
			}

			$this->registry->registerReference('method', $method);
			$this->registry->registerReference('request', $_REQUEST);
			$this->registry->registerReference('get', $_GET);
			$this->registry->registerReference('post', $_POST);
			$this->registry->registerReference('put', $_PUT);
			$this->registry->registerReference('delete', $_DELETE);
			$this->registry->registerReference('cookie', $_COOKIE);

			$_FILES = $this->_processFiles($_FILES);

			unset($method);
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
	 * Method to process prepared structure of files
	 *
	 * @param string $node
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	private function _processFiles(array &$node) {
		try {
			$files = $this->_prepareFiles($node, new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><files></files>'));

			foreach($files as $k => $v) {
				$this->registry->registerReference($k, new \Glue\Objects\File($v['tmp_name'], $v['name'], $v['type'], $v['size'], $v['error']));
			}

			$return = $this->registry->getReference('files');

			unset($node, $files, $k, $v);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to process structure of files
	 *
	 * @param string $value
	 * @param object $parent [optional]
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	private function _prepareFiles(&$value, \SimpleXMLElement &$parent) {
		try {
			static $files = array();

			if(is_array($value)) {
				foreach($value as $k => $v) {
					if(!isset($parent->$k)) {
						$parent->addChild($k);
					}

					$this->_prepareFiles($value[$k], $parent->$k);
				}

				unset($k, $v);
			} else {
				$node = $parent->xpath('parent::*/ancestor::*');

				array_push($node, $parent);

				$node = array_merge($node, $parent->xpath('parent::*'));

				foreach($node as $k => $v) {
					$node[$k] = $v->getName();
				}

				$key  = array_pop($node);
				$node = implode('.', $node);

				if(!isset($this->files[$node])) {
					$files[$node] = array();
				}

				$files[$node][$key] = $value;

				unset($k, $v, $node, $key);
			}

			unset($value, $parent);

			return $files;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to sanitize node
	 *
	 * @param string $node
	 *
	 * @return string
	 *
	 * @throw \RuntimeException
	 */
	private function _clean(&$node) {
		try {
			if(is_array($node)) {
				$node = array_map(array(&$this, '_clean'), $node);
			} else {
				$node = trim($node);

				if(get_magic_quotes_gpc()) {
					$node = stripslashes($node);
				}

				$node = preg_replace('/(\r\n|\r|\n)/', chr(10), $node);
				$node = str_replace("\0", '', $node);
			}

			return $node;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>