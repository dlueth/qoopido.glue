<?php
namespace {
	/**
	 * Define constant for base directory
	 */
	define('__BASE__', dirname($_SERVER['SCRIPT_FILENAME']));

	/**
	 * Define missing constants for some versions of PHP
	 */
	if(defined('E_STRICT') === false) {
		define('E_STRICT', 2048);
	}

	if(defined('E_RECOVERABLE_ERROR') === false) {
		define('E_RECOVERABLE_ERROR', 4096);
	}

	if(defined('E_DEPRECATED') === false) {
		define('E_DEPRECATED', 8192);
	}

	if(defined('E_USER_DEPRECATED') === false) {
		define('E_USER_DEPRECATED', 16384);
	}

	if(defined('E_ALL') === false) {
		define('E_ALL', 30719);
	}


	if(defined('DEBUG_BACKTRACE_PROVIDE_OBJECT') === false) {
		define('DEBUG_BACKTRACE_PROVIDE_OBJECT', 1);
	}

	if(defined('DEBUG_BACKTRACE_IGNORE_ARGS') === false) {
		define('DEBUG_BACKTRACE_IGNORE_ARGS', 2);
	}

	/**
	 * Constants for scopes
	 */
	const SCOPE_GLOBAL = 1;
	const SCOPE_LOCAL  = 2;
	const SCOPE_ALL    = 3;

	/**
	 * Constants for exception messages
	 */
	const EXCEPTION_CLASS_INITIALIZE   = '{class}: failed to initialize';
	const EXCEPTION_CLASS_SINGLETON    = '{class}: singleton already initialized';
	const EXCEPTION_CLASS_CONTROLLER   = '{class}: controller must extend \Glue\Abstracts\Controller';
	const EXCEPTION_METHOD_FAILED      = '{method}: failed unexpectedly';
	const EXCEPTION_METHOD_PERMISSIONS = '{method}: insufficient permissions';
	const EXCEPTION_METHOD_CONTEXT     = '{method}: unavailable in context';
	const EXCEPTION_PARAMETER          = '{method}: parameter {parameter} invalid';
	const EXCEPTION_EXTENSION_MISSING  = '{class}: extension {extension} not installed';
	const EXCEPTION_FUNCTION_MISSING   = '{class}: function {function} does not exist';
	const EXCEPTION_SETTING_MISSING    = '{class}: PHP.ini directive {setting} is not set';

	/**
	 * set default timezone
	 */
	@date_default_timezone_set(@date_default_timezone_get());

	/**
	 * alter settings in PHP.ini
	 */
	ini_set('default_charset',                'UTF-8');
	ini_set('error_reporting',                E_ALL ^ E_NOTICE);
	ini_set('display_errors',                 1);
	ini_set('register_globals',               0);
	ini_set('magic_quotes_gpc',               0);
	ini_set('allow_call_time_pass_reference', 0);
	ini_set('short_open_tag',                 0);

	class CoreException extends LogicException {}
}

namespace Glue {
	/**
	 * Bootstrap process + further core processing
	 *
	 * @require PHP "ZLIB" extension [optional]
	 *
	 * @event glue.core.render.pre()
	 * @event glue.core.render.post(string &$content)
	 * @event glue.core.output.pre()
	 * @event glue.core.output.post()
	 *
	 * @author Dirk Lüth <dirk@qoopido.de>
	 *
	 * @todo Implement "\Glue\Objects\Cache\Sqlite"
	 * @todo Implement database session storage
	 * @todo "\Glue\Objects\Image": check caman.js for additional features to implement
	 * @todo "\Glue\Objects\Image": check caman.js for preset features
	 * @todo "\Glue\Handler\View\Xml": Implement handling for array and string data (general check)
	 * @todo "\Glue\Objects\Form\Elements": Implement new exception concept
	 * @todo "\Glue\Objects\Query": Implement new exception concept
	 * @todo "\Glue\Objects\Query\Insert": Implement support for "ON DUPLICATE KEY UPDATE"
	 * @todo "\Glue\Objects\Query\Select": Implement support for "UNION"
	 */
	final class Core {
		/**
		 * Version
		 */
		const VERSION = '0.9.9';

		/**
		 * Private property to store core path information
		 *
		 * @array
		 */
		private $path;

		/**
		 * Private property to store core profiling information
		 *
		 * @string
		 */
		private $profile;

		/**
		 * Class constructor
		 *
		 * @throw \CoreException
		 */
		final public function __construct() {
			try {
				// initiate path
				$this->path = array(
					'global' => str_replace('\\', '/', __BASE__),
					'local'  => NULL
				);

				// initiate profile
				$this->profile = array(
					'start'    => microtime(true),
					'end'      => NULL,
					'duration' => NULL,
					'memory'   => NULL,
					'files'    => NULL
				);

				// register autoloader
				spl_autoload_register(array($this, '_autoloader'));

				// initialize event dispatcher
				$dispatcher = Dispatcher::getInstance();

				// initialize factory
				$factory = Factory::getInstance();

				// register with factory
				$factory->register($this);

				// initialize error/exception handling
				$factory->load('\Glue\Components\Exception');
				$factory->load('\Glue\Listener\Exception', $this->path);

				// initialize core components
				$configuration = $factory->load('\Glue\Components\Configuration');
				$url           = $factory->load('\Glue\Components\Url');
				$request       = $factory->load('\Glue\Components\Request');
				if($configuration->get('Components.Routing.@attributes.enabled') === true) {
					$routing   = $factory->load('\Glue\Components\Routing');
				}
				$client        = $factory->load('\Glue\Components\Client');
				$environment   = $factory->load('\Glue\Components\Environment');
				$header        = $factory->load('\Glue\Components\Header');
				if($configuration->get('Components.Session.@attributes.enabled') === true) {
					$session   = $factory->load('\Glue\Components\Session');
				}
			} catch(\Exception $exception) {
				throw new \CoreException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
			}

			// fetch configuration
			$settings    = $configuration->get(__CLASS__);
			$compression = $environment->get('compression');

			// check core cache
			if($settings['cache']['@attributes']['enabled'] === true) {
				$id = $this->path['local'] . '/.cache/' . strtolower(__CLASS__) . '/' . $environment->get('theme') . '/' . $environment->get('language') . '/' . sha1(serialize(array($environment->get('node'), $compression)));

				if(extension_loaded('apc') === true) {
					$cache = \Glue\Objects\Cache\Apc::getInstance($id);
				} else {
					$cache = \Glue\Objects\Cache\File::getInstance($id);
				}

				if(($content = $cache->get()) !== false) {
					$maxage                  = ($cache->timestamp + $cache->lifetime) - time();
					$header                  = $cache->extras['header'];
					$header['Date']          = array(gmdate('D, d M Y H:i:s', time()) . ' GMT');
					$header['Cache-Control'] = array('public', 'max-age=' . $maxage, 's-maxage=' . $maxage, 'must-revalidate', 'proxy-revalidate');

					if((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $cache->extras['etag']) || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, d M Y H:i:s', $cache->timestamp) . ' GMT')) {
						$header['HTTP/1.1 304 Not Modified'] = NULL;
						$content = NULL;
					}

					$dispatcher->notify(new \Glue\Event('glue.core.output.pre'));

					$this->_output($content, $header);

					$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

					exit;
				} else {
					$environment->set('lifetime', $settings['cache']['lifetime']);
				}
			}

			// load view adapter
			$view = $factory->load('\Glue\Adapter\View');

			// run controller
			$controller = $environment->get('controller');

			if($controller !== NULL) {
				$controller = new $controller();
			}

			// render view
			$dispatcher->notify(new \Glue\Event('glue.core.render.pre'));

			$view->register('core', $this);

			$content = \Glue\Helper\Modifier::convertCharset($view->render());

			$dispatcher->notify(new \Glue\Event('glue.core.render.post', array(&$content)));

			if($compression !== false) {
				$content = gzencode($content, $compression);
			}

			// initialize header
			$etag       = md5($content);
			$generation = $environment->get('generation');
			$lifetime   = strtotime($environment->get('lifetime'), $generation);

			$header->set('Date', gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
			$header->set('Vary', 'Accept', true);
			$header->set('Vary', 'Cache-Control');
			$header->set('ETag', $etag, true);
			$header->set('Last-Modified', gmdate('D, d M Y H:i:s', $generation) . ' GMT', true);

			if($environment->get('lifetime') !== NULL) {
				$header->set('Expires', gmdate('D, d M Y H:i:s', $lifetime) . ' GMT', true);
				$header->set('Cache-Control', 'public', true);
				$header->set('Cache-Control', 'max-age=' . ($lifetime - $generation));
				$header->set('Cache-Control', 's-maxage=' . ($lifetime - $generation));
				$header->set('Cache-Control', 'must-revalidate');
				$header->set('Cache-Control', 'proxy-revalidate');
			}

			$header->set('Content-Type', $environment->get('mimetype') . '; charset=' . $environment->get('characterset'), true);
			$header->set('Content-Length', strlen($content), true);

			if($compression !== false) {
				$header->set('Vary', 'Accept-Encoding');
				$header->set('Content-Encoding', 'gzip', true);
			}

			$dispatcher->notify(new \Glue\Event('glue.core.output.pre'));

			// check etag status
			if((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, d M Y H:i:s', $generation) . ' GMT')) {
				$header = $header->get();
				$header['HTTP/1.1 304 Not Modified'] = NULL;

				$this->_output(NULL, $header);

				$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

				exit;
			} else {
				$this->_output($content, $header->get());
			}

			$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

			// write core cache
			if($settings['cache']['@attributes']['enabled'] === true) {
				$cache
					->setLifetime(strtotime($settings['cache']['lifetime']))
					->setExtras(array('etag' => $etag, 'header' => $header->get()))
					->setData($content)
					->set();
			}

			unset($dispatcher, $factory, $configuration, $url, $request, $routing, $server, $client, $environment, $header, $session, $settings, $compression, $cache, $view, $controller, $content, $maxage, $etag, $generation, $lifetime);

			exit;
		}

		/**
		 * Private method for content output to client
		 *
		 * @param string $content
		 * @param array $header [optional]
		 */
		final private function _output($content, array $header = array()) {
			foreach($header as $name => $values) {
				if(is_array($values)) {
					foreach($values as $index => $value) {
						if($index === 0) {
							@header($name . ': ' . $value, true);
						} else {
							@header($name . ': ' . $value, false);
						}
					}
				} else {
					@header($name, true);
				}
			}

			echo $content;

			unset($content, $header, $name, $values, $index, $value);
		}

		/**
		 * Magic method for retrieving values of unkown or restricted properties
		 *
		 * @param string $property
		 *
		 * @return mixed
		 */
		final public function &__get($property) {
			switch($property) {
				case 'path':
					return $this->path;
					break;
				case 'version':
					$return = self::VERSION;
					return $return;
					break;
				case 'profile':
					$this->profile['end']      = microtime(true);
					$this->profile['duration'] = $this->profile['end'] - $this->profile['start'] . ' s';
					$this->profile['memory']   = \Glue\Helper\Converter::bytes2human(memory_get_peak_usage());
					$this->profile['files']    = get_included_files();

					return $this->profile;
					break;
			}
		}

		/**
		 * Magic method for checking the existance of unkown or restricted properties
		 *
		 * @param string $property
		 *
		 * @return bool
		 */
		final public function __isset($property) {
			switch($property) {
				case 'path';
				case 'version';
				case 'profile':
					return true;
					break;
				default:
					return false;
					break;
			}
		}

		/**
		 * Private method for autoloading all Glue classes
		 *
		 * @param string $classname
		 */
		final private function _autoloader($classname) {
			if(!preg_match('/^Glue\\\/', $classname)) {
				return;
			}

			$id        = preg_replace('/^Glue\\\/', '', $classname);
			$classname = '\Glue\\' . $id;
			$segments  = explode('\\', $id);

			switch($segments[0]) {
				case 'Custom':
					array_shift($segments);

					$segments = strtolower(implode('/', $segments));
					$path     = $this->path['local'] . '/.custom/' . $segments . '.class.php';

					if(!is_file($path)) {
						$path = $this->path['global'] . '/.custom/' . $segments . '.class.php';
					}

					break;
				case 'Controller':
					array_shift($segments);

					$path   = $this->path['local'] . '/.controller/' . strtolower(implode('/', $segments)) . '.class.php';
					break;
				default:
					$path   = $this->path['global'] . '/.core/' . strtolower(implode('/', $segments)) . '.class.php';
					break;
			}

			if(is_file($path)) {
				require($path);

				if(method_exists($classname, '__once')) {
					$classname::__once();
				}
			}

			unset($id, $classname, $segments, $path);
		}
	}
}
?>