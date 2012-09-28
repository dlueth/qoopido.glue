<?php
namespace Glue\Component;

/**
 * Component for configuration handling
 *
 * @require PHP "SIMPLEXML" extension
 *
 * @event glue.component.configuration.load.pre(array $files) > load()
 * @event glue.component.configuration.load.post(array $files, array $data) > load()
 *
 * @listen glue.gateway.view.render.pre > onPreRender()
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
final class Configuration extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Property to store core path information
	 *
	 * @array
	 */
	private $path = NULL;

	/**
	 * Property to provide registry
	 *
	 * @object \Glue\Entity\Registry
	 */
	private $registry = NULL;

	/**
	 * Event listener
	 */
	final public function onPreRender() {
		\Glue\Factory::getInstance()->get('\Glue\Gateway\View')->register('configuration', $this->registry->get());
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
			$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.gateway.view.render.pre');

			$this->registry =  new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ);

			$path =& \Glue\Factory::getInstance()->get('\Glue\Core')->path;

			$files = array(
				$path['global'] . '/.configuration/.default.xml',
				$path['global'] . '/.configuration/' . $_SERVER['SERVER_ADDR'] . '.xml',
				$path['global'] . '/.configuration/' . $_SERVER['SERVER_NAME'] . '.xml'
			);

			$id = $path['global'] . '/.cache/' . strtolower(__CLASS__) . '/' . sha1(serialize($files));

			if(extension_loaded('apc') === true) {
				$cache = \Glue\Entity\Cache\Apc::getInstance($id);
			} else {
				$cache = \Glue\Entity\Cache\File::getInstance($id);
			}

			$cache->setDependencies($files);

			if(($data = $cache->get()) === false) {
				if(($data = \Glue\Helper\General::loadConfiguration($cache->dependencies)) !== false) {
					$cache->setData($data)->set();
				}
			}

			if($data !== false) {
				$this->registry->set(NULL, $data);
			}

			unset($path, $files, $id, $cache, $data);
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
}
?>