<?php
namespace Glue\Components;

/**
 * Component for session abstraction
 *
 * @require PHP "SESSION" extension
 *
 * @event glue.components.session.close.pre > close()
 * @event glue.components.session.close.post > close()
 * @event glue.components.session.destroy.pre > destroy()
 * @event glue.components.session.destroy.post > destroy()
 *
 * @listen glue.adapter.view.pre.render > onPreRender()
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Session extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Property to provide registry
	 *
	 * @object \Glue\Objects\Registry
	 */
	private $registry = NULL;

	/**
	 * Event listener
	 */
	final public function onPreRender() {
		\Glue\Factory::getInstance()->get('\Glue\Adapter\View')->register('session', $this->registry->get());
	}

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('session') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'SESSION'), EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final protected function __initialize() {
		try {
			$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.adapter.view.render.pre');

			$this->registry = new \Glue\Objects\Registry($this, \Glue\Objects\Registry::PERMISSION_ALL);

			$settings = \Glue\Components\Configuration::getInstance()->get(__CLASS__);
			$request  = \Glue\Components\Request::getInstance();

			// alter PHP.ini directives
				ini_set('session.use_only_cookies', 1);
				ini_set('session.use_cookies',      1);
				ini_set('session.use_trans_sid',    0);
				ini_set('session.auto_start',       0);
				ini_set('session.name',             $settings['name']);

				if(isset($settings['lifetime'])) {
					ini_set('session.gc_maxlifetime', $settings['lifetime']);
				}

				if(isset($settings['directory']) && isset($settings['directory']['path']) && isset($settings['directory']['scope'])) {
					$settings['directory'] = \Glue\Helper\Modifier::cleanPath(\Glue\Components\Environment::getInstance()->get('path.' . $settings['directory']['scope']) . '/' . $settings['directory']['path']) . '/' . $_SERVER['SERVER_NAME'] . '/' . \Glue\Components\Environment::getInstance()->get('site');

					if(is_dir($settings['directory']) === false) {
						mkdir($settings['directory'], 0750, true);
					}

					ini_set('session.save_path', $settings['directory']);
				}

				session_start();

			// assign variables
				$page = sha1(\Glue\Components\Environment::getInstance()->get('id'));

			// unset session variable
				$request->unregister('request.' . $settings['name']);
				$request->unregister('get.' . $settings['name']);
				$request->unregister('post.' . $settings['name']);
				$request->unregister('put.' . $settings['name']);
				$request->unregister('delete.' . $settings['name']);
				$request->unregister('cookie.' . $settings['name']);

			// assign data
				$data['name']       =  $settings['name'];
				$data['id']         =  session_id();

				$_SESSION['data']         = (isset($_SESSION['data'])) ? $_SESSION['data'] : array();
				$_SESSION['pages']        = (isset($_SESSION['pages'])) ? $_SESSION['pages'] : array();
				$_SESSION['pages'][$page] = (isset($_SESSION['pages'][$page])) ? $_SESSION['pages'][$page] : array();

				$data['data'] =& $_SESSION['data'];
				$data['page'] =& $_SESSION['pages'][$page];

				$this->registry->set(NULL, $data);

			unset($settings, $request, $page, $data);
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
	 * Method to manually close session
	 *
	 * @throw \RuntimeException
	 */
	final public function close() {
		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.close.pre'));

			session_write_close();

			$this->dispatcher->notify(new \Glue\Event($this->id . '.close.post'));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to manually destroy session
	 *
	 * @throw \RuntimeException
	 */
	final public function destroy() {
		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.destroy.pre'));

			unset($this->registry);
			unset($_SESSION['data']);
			unset($_SESSION['pages']);

			session_unset();
			session_destroy();

			$this->dispatcher->notify(new \Glue\Event($this->id . '.destroy.post'));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>