<?php
namespace Glue\Adapter\View;

/**
 * View adapter for Dwoo
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Dwoo extends \Glue\Abstracts\Adapter\View {
	/**
	 * Method to fetch the view's output
	 *
	 * @throw \RuntimeException
	 */
	public function fetch() {
		try {
			$environment = self::$environment->get();
			$id          = $environment['theme'] . '/' . $environment['language'] . '/' . $environment['node'];

			require_once($environment['path']['global'] . '/.libraries/Dwoo/dwooAutoload.php');

			// set directories
				$directories              = array();
				$directories['cache']     = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . __CLASS__) . '/cache';
				$directories['compile']   = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . __CLASS__) . '/compile';
				$directories['plugins']   = array(
					\Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.libraries/Glue/Plugins/Adapter/View/Dwoo'),
					\Glue\Helper\Modifier::cleanPath($environment['path']['global'] . '/.libraries/Glue/Plugins/Adapter/View/Dwoo')
				);
				$directories['templates'] = array(
					$environment['path']['local'] . '/.templates/view/dwoo/' . $environment['theme'] . '/' . $environment['language'] . '/',
					$environment['path']['global'] . '/.templates/view/dwoo/' . $environment['theme'] . '/' . $environment['language'] . '/',
					$environment['path']['local'] . '/.templates/view/dwoo/' . $environment['language'] . '/',
					$environment['path']['global'] . '/.templates/view/dwoo/' . $environment['language'] . '/',
					$environment['path']['local'] . '/.templates/view/dwoo/',
					$environment['path']['global'] . '/.templates/view/dwoo/'
				);

			// initialize directories
				if(!is_dir($directories['cache'])) {
					\Glue\Helper\Filesystem::createDirectory($directories['cache']);
				}

				if(!is_dir($directories['compile'])) {
					\Glue\Helper\Filesystem::createDirectory($directories['compile']);
				}

			// remove non existant template directories
				foreach($directories['templates'] as $index => $directory) {
					if(is_dir($directory) === false) {
						unset($directories['templates'][$index]);
					}
				}

			// remove non existant plugin directories
				foreach($directories['plugins'] as $index => $directory) {
					if(is_dir($directory) === false) {
						unset($directories['plugins'][$index]);
					}
				}

			$dwoo = new \Dwoo($directories['compile'], $directories['cache']);
			$dwoo->setSecurityPolicy();
			$loader = $dwoo->getLoader();

			foreach($directories['plugins'] as $directory) {
				if(is_dir($directory)) {
					$loader->addDirectory($directory);
				}
			}

			$template = new \Dwoo_Template_File($this->template . '.tpl', NULL, $id, $id, $directories['templates']);
			$return   = $dwoo->get($template, $this->gateway->get());

			unset($environment, $id, $directories, $dwoo, $loader, $template);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>