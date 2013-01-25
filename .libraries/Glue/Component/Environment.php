<?php
namespace Glue\Component;

/**
 * Component providing global environment
 *
 * @require PHP "ZLIB" extension [optional]
 *
 * @event glue.component.environment.process.pre() > _process()
 * @event glue.component.environment.process.post() > _process()
 *
 * @listen glue.gateway.view.render.pre > onPreRender()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Environment extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to provide registry
	 *
	 * @object \Glue\Entity\Registry
	 */
	private $registry = NULL;

	/**
	 * Event listener
	 */
	final public function onPreRender() {
		\Glue\Factory::getInstance()->get('\Glue\Gateway\View')->register('environment', $this->registry->get());
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 * @throw \LogicException
	 */
	final protected function __initialize() {
		try {
			$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.gateway.view.render.pre');

			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_READ | \Glue\Entity\Registry::PERMISSION_SET | \Glue\Entity\Registry::PERMISSION_REGISTER);

			$settings = \Glue\Component\Configuration::getInstance()->get(__CLASS__);
			$url      = \Glue\Component\Url::getInstance();
			$data     = array();

			// alter default timezone
				date_default_timezone_set($settings['defaults']['timezone']);

			// initialize primary environment variables
				$data['raw']                  = \Glue\Helper\Modifier::cleanPath($_REQUEST['Glue']['node'], true);
				$data['id']                   = false;
				$data['node']                 = false;
				$data['alias']                = false;
				$data['slug']                 = false;
				$data['site']                 = $url->get('switches.site') ?: $settings['defaults']['site'];
				$data['theme']                = $url->get('switches.theme') ?: $settings['defaults']['theme'];
				$data['language']             = $url->get('switches.language') ?: $settings['defaults']['language'];
				$data['characterset']         = ini_get('default_charset');

			// set primary environment variables
				$data['node']                 = (!empty($data['raw'])) ? $data['raw'] : \Glue\Helper\Modifier::cleanPath($settings['defaults']['node'], true);
				$data['alias']                = str_replace('/', '.', $data['node']);
				$data['slug']                 = str_replace('.', '/', preg_replace('/[^\w.]/', '', $data['alias']));
				$data['id']                   = NULL;

			// set secondary environment variables
				$data['mimetype']             = false;
				$data['urlrewriting']         = (bool) $settings['urlrewriting']['@attributes']['enabled'];
				$data['compression']          = ((bool) $settings['compression']['@attributes']['enabled'] !== true || !isset($settings['compression']['@attributes']['level']) || empty($settings['compression']['@attributes']['level']) || !preg_match('/^\d{1,1}$/', $settings['compression']['@attributes']['level'])) ? false : (int) $settings['compression']['@attributes']['level'];
				$data['compression']          = ($data['compression'] !== false && extension_loaded('zlib') === true && preg_match('/gzip|deflate/', \Glue\Component\Client::getInstance()->get('accept.encoding'))) ? $data['compression'] : false;
				$data['timezone']             = $settings['defaults']['timezone'];
				$data['lifetime']             = $settings['defaults']['lifetime'];
				$data['time']                 = time();
				$data['generation']           = $data['time'];
				$data['ssl']                  = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'on') || (isset($_SERVER['SSL_PROTOCOL']) && !empty($_SERVER['SSL_PROTOCOL']))) ? true : false;

			// set path
				$data['path']                 =& \Glue\Factory::getInstance()->get('\Glue\Core')->path;

			// set URLs
				$data['url']                  = array();
				$data['url']['absolute']      = ($data['ssl'] == true) ? 'https://' : 'http://';
				$data['url']['absolute']     .= $_SERVER['SERVER_NAME'] . preg_replace('/^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '/') . '/', '', dirname($_SERVER['SCRIPT_NAME']));
				$data['url']['absolute']      = preg_replace('/\/*$/', '', $data['url']['absolute']) . '/';
				$data['url']['relative']      = parse_url($data['url']['absolute']);
				$data['url']['relative']      = $data['url']['relative']['path'];

			// set modifier
				$data['modifier']             = array();

			// set controller
				$data['controller']           = '\Glue\Controller\\' . implode('\\', array_map('ucfirst', explode('/', $data['slug'])));

				if(!class_exists($data['controller'])) {
					$data['controller'] = '\Glue\Controller\General';

					if(!class_exists($data['controller'])) {
						$data['controller'] = NULL;
					}
				}

				if($data['controller'] !== NULL && !is_subclass_of($data['controller'], '\Glue\Abstracts\Controller')) {
					throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_CONTROLLER));
				}

			// clean request
				$request = \Glue\Component\Request::getInstance();
				$request->unregister('request.Glue');
				$request->unregister('get.Glue');
				$request->unregister('post.Glue');
				$request->unregister('put.Glue');
				$request->unregister('delete.Glue');
				$request->unregister('cookie.Glue');

			// set registry
				$this->registry->set(NULL, $data);

			// process id
				$this->_process();

			unset($settings, $url, $data);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to build id for environment/caching
	 *
	 * @return void
	 */
	final protected function _process() {
		$this->dispatcher->notify(new \Glue\Event($this->id . '.process.pre'));

		$data     = $this->registry->get();
		$id       = 'site:' . $data['site'] . '/theme:' . $data['theme'] . '/language:' . $data['language'] . '/' . $data['node'] . '/';
		$modifier = $this->registry->get('modifier');

		if(count($modifier) > 0) {
			$id .= sha1(json_encode($modifier)) . '/';
		}

		$this->registry->set('id', $id);

		$this->dispatcher->notify(new \Glue\Event($this->id . '.process.post'));

		unset($data, $id, $modifier);
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