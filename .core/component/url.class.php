<?php
namespace Glue\Component;

/**
 * Component for url handling
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Url extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to provide registry
	 *
	 * @object \Glue\Objects\Registry
	 */
	private $registry = NULL;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$path          =& \Glue\Factory::getInstance()->get('\Glue\Core')->path;
			$configuration = \Glue\Component\Configuration::getInstance();
			$settings      =  $configuration->get(__CLASS__);

			$this->registry = new \Glue\Objects\Registry($this, \Glue\Objects\Registry::PERMISSION_READ);

			$this->registry->register('switches', array());

			if(isset($settings['switches']) && is_array($settings['switches'])) {
				foreach($settings['switches'] as $switch => $expression) {
					$this->registry->register('switches.' . $switch, NULL);
				}
			}

			if(isset($_REQUEST['Glue']) && isset($_REQUEST['Glue']['node']) && !empty($_REQUEST['Glue']['node'])) {
				$_REQUEST['Glue']['node'] = \Glue\Helper\Modifier::cleanPath($_REQUEST['Glue']['node'], true);

				// process switches
					if(isset($settings['switches']) && is_array($settings['switches'])) {
						$identifier = (isset($settings['identifier']['switches'])) ? preg_quote($settings['identifier']['switches'], '/') : '!';

						foreach($settings['switches'] as $switch => $expression) {
							if(preg_match_all('/' . $identifier . '(' . $expression . ')/', $_REQUEST['Glue']['node'], $matches, PREG_SET_ORDER)) {
								$this->registry->set('switches.' . $switch, $matches[count($matches) - 1][1]);

								// remove switch
									$_REQUEST['Glue']['node'] = preg_replace('/!(' . $expression . ')/', '', $_REQUEST['Glue']['node']);
							}
						}

						unset($switch, $expression);
					}

				$_REQUEST['Glue']['node'] = \Glue\Helper\Modifier::cleanPath($_REQUEST['Glue']['node'], true);

				unset($identifier, $matches);
			}

			$path['local'] = $path['global'] . '/' . ($this->registry->get('switches.site') ?: $configuration->get('\Glue\Component\Environment\defaults\site'));

			unset($path, $configuration, $settings);
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