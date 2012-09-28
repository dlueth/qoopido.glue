<?php
namespace Glue\Adapter\View;

/**
 * View adapter for Dwoo
 *
 * @author Dirk Lüth <info@qoopido.de>
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

			require_once($environment['path']['global'] . '/.core/libraries/dwoo/lib/dwooAutoload.php');

			// set directories
				$directories              = array();
				$directories['cache']     = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . strtolower(__CLASS__)) . '/cache';
				$directories['compile']   = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . strtolower(__CLASS__)) . '/compile';
				$directories['plugins']   = array(
					\Glue\Helper\Modifier::cleanPath($environment['path']['global'] . '/.core/plugins/adapter/view/dwoo'),
					\Glue\Helper\Modifier::cleanPath($environment['path']['global'] . '/.custom/plugins/adapter/view/dwoo'),
					\Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.custom/plugins/adapter/view/dwoo'),
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

			$dwoo = new \Dwoo($directories['compile'], $directories['cache']);

			$dwoo->setSecurityPolicy();

			$loader = $dwoo->getLoader();

			foreach($directories['plugins'] as $directory) {
				if(is_dir($directory)) {
					$loader->addDirectory($directory);
				}
			}

			$template = new \Dwoo_Template_File($this->template . '.tpl', NULL, $id, $id, $directories['templates']);

			$return = $dwoo->get($template, $this->gateway->get());

			unset($environment, $id, $directories, $dwoo, $loader, $template);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>