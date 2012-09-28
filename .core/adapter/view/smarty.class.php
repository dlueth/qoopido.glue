<?php
namespace Glue\Adapter\View;

/**
 * View adapter for Smarty
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
class Smarty extends \Glue\Abstracts\Adapter\View {
	/**
	 * Method to fetch the view's output
	 *
	 * @throw \RuntimeException
	 */
	final public function fetch() {
		try {
			$environment = self::$environment->get();

			require_once($environment['path']['global'] . '/.core/libraries/smarty/distribution/libs/Smarty.class.php');

			// set directories
				$directories              = array();
				$directories['cache']     = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . strtolower(__CLASS__)) . '/cache';
				$directories['compile']   = \Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.cache/' . strtolower(__CLASS__)) . '/compile';
				$directories['plugins']   = array(
					\Glue\Helper\Modifier::cleanPath($environment['path']['global'] . '/.core/plugins/adapter/view/smarty'),
					\Glue\Helper\Modifier::cleanPath($environment['path']['global'] . '/.custom/plugins/adapter/view/smarty'),
					\Glue\Helper\Modifier::cleanPath($environment['path']['local'] . '/.custom/plugins/adapter/view/smarty'),
				);
				$directories['templates'] = array(
					$environment['path']['local'] . '/.templates/view/smarty/' . $environment['theme'] . '/' . $environment['language'] . '/',
					$environment['path']['global'] . '/.templates/view/smarty/' . $environment['theme'] . '/' . $environment['language'] . '/',
					$environment['path']['local'] . '/.templates/view/smarty/' . $environment['language'] . '/',
					$environment['path']['global'] . '/.templates/view/smarty/' . $environment['language'] . '/',
					$environment['path']['local'] . '/.templates/view/smarty/',
					$environment['path']['global'] . '/.templates/view/smarty/'
				);
				$directories['configs'] = array(
					$environment['path']['local'] . '/.templates/view/smarty/' . $environment['theme'] . '/' . $environment['language'] . '/configs/',
					$environment['path']['global'] . '/.templates/view/smarty/' . $environment['theme'] . '/' . $environment['language'] . '/configs/',
					$environment['path']['local'] . '/.templates/view/smarty/' . $environment['language'] . '/configs/',
					$environment['path']['global'] . '/.templates/view/smarty/' . $environment['language'] . '/configs/',
					$environment['path']['local'] . '/.templates/view/smarty/configs/',
					$environment['path']['global'] . '/.templates/view/smarty/configs/'
				);

			// initialize directories
				if(!is_dir($directories['cache'])) {
					\Glue\Helper\Filesystem::createDirectory($directories['cache']);
				}

				if(!is_dir($directories['compile'])) {
					\Glue\Helper\Filesystem::createDirectory($directories['compile']);
				}

			$smarty = new \Smarty();
			$smarty->setTemplateDir($directories['templates']);
			$smarty->addPluginsDir($directories['plugins']);
			$smarty->setConfigDir($directories['configs']);
			$smarty->setCompileDir($directories['compile']);
			$smarty->setCacheDir($directories['cache']);

			$smarty->assign($this->gateway->get());

			$template = $this->template . '.tpl';
			$return   = $smarty->fetch($template);

			unset($environment, $directories, $smarty, $template);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>