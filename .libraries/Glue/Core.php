<?php
namespace {
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
	 * Define constant for base directory
	 */
	define('GLUE_DIRECTORY_BASE', dirname($_SERVER['SCRIPT_FILENAME']));

	/**
	 * Constants for scopes
	 */
	const GLUE_SCOPE_GLOBAL = 1;
	const GLUE_SCOPE_LOCAL  = 2;
	const GLUE_SCOPE_ALL    = 3;

	/**
	 * Constants for exception messages
	 */
	const GLUE_EXCEPTION_CLASS_INITIALIZE   = '{class}: failed to initialize';
	const GLUE_EXCEPTION_CLASS_SINGLETON    = '{class}: singleton already initialized';
	const GLUE_EXCEPTION_CLASS_CONTROLLER   = '{class}: controller must extend \Glue\Abstract\Controller';
	const GLUE_EXCEPTION_METHOD_FAILED      = '{method}: failed unexpectedly';
	const GLUE_EXCEPTION_METHOD_PERMISSIONS = '{method}: insufficient permissions';
	const GLUE_EXCEPTION_METHOD_CONTEXT     = '{method}: unavailable in context';
	const GLUE_EXCEPTION_PARAMETER          = '{method}: parameter {parameter} invalid';
	const GLUE_EXCEPTION_EXTENSION_MISSING  = '{class}: extension {extension} not installed';
	const GLUE_EXCEPTION_FUNCTION_MISSING   = '{class}: function {function} does not exist';
	const GLUE_EXCEPTION_SETTING_MISSING    = '{class}: PHP.ini directive {setting} is not set';
}

namespace Glue {
	class CoreException extends \LogicException {}

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
	 * @author Dirk Lüth <info@qoopido.com>
	 *
	 * @todo Implement "\Glue\Entity\Cache\Sqlite"
	 * @todo Implement database session storage
	 * @todo "\Glue\Entity\Image": check caman.js for additional features to implement
	 * @todo "\Glue\Entity\Image": check caman.js for preset features
	 * @todo "\Glue\Adapter\View\Xml": Implement handling for array and string data (general check)
	 * @todo "\Glue\Entity\Form\Elements": Implement new exception concept
	 * @todo "\Glue\Entity\Query": Implement new exception concept
	 * @todo "\Glue\Entity\Query\Insert": Implement support for "ON DUPLICATE KEY UPDATE"
	 * @todo "\Glue\Entity\Query\Select": Implement support for "UNION"
	 */
	final class Core {
		/**
		 * Version
		 */
		const VERSION = '1.2.0';

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
		 * Private property to store environment data
		 *
		 * @array
		 */
		private $environment;

		/**
		 * Class constructor
		 *
		 * @throw \Glue\CoreException
		 */
		final public function __construct() {
			$_REQUEST['Glue']['node']     = (isset($_REQUEST['Glue']['node']) && !empty($_REQUEST['Glue']['node'])) ? $_REQUEST['Glue']['node'] : NULL;
			$_REQUEST['Glue']['modifier'] = array();

			$node = $_REQUEST['Glue']['node'];

			try {
				// ignore user abort
				ignore_user_abort(true);

				// initiate path
				$this->path = array(
					'global' => str_replace('\\', DIRECTORY_SEPARATOR, GLUE_DIRECTORY_BASE),
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
				$factory->load('\Glue\Component\Exception');
				$factory->load('\Glue\Listener\Exception', $this->path);

				// initialize core components
				$configuration = $factory->load('\Glue\Component\Configuration');
				$url           = $factory->load('\Glue\Component\Url');
				$request       = $factory->load('\Glue\Component\Request');
				$routing       = $factory->load('\Glue\Component\Routing');
				$client        = $factory->load('\Glue\Component\Client');
				$environment   = $factory->load('\Glue\Component\Environment');
				$header        = $factory->load('\Glue\Component\Header');
				if($configuration->get('Component.Session.@attributes.enabled') === true) {
					$session   = $factory->load('\Glue\Component\Session');
				}

				$this->environment =& $environment;
			} catch(\Exception $exception) {
				throw new \Glue\CoreException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
			}

			// fetch configuration
			$settings    = $configuration->get(__CLASS__);
			$compression = $environment->get('compression');

			// check core cache
			$cacheable = (isset($settings['cache']['exclude'])) ? !in_array($node, (array) $settings['cache']['exclude']) : true;

			if($settings['cache']['@attributes']['enabled'] === true && $cacheable === true) {
				$id = $this->path['local'] . '/.cache/' . __CLASS__ . '/' . sha1(serialize(array($environment->get('id'), $compression)));

				if(extension_loaded('apc') === true) {
					$cache = \Glue\Entity\Cache\Apc::getInstance($id);
				} else {
					$cache = \Glue\Entity\Cache\File::getInstance($id);
				}

				if(($content = $cache->get()) !== false) {
					$maxage                  = ($cache->timestamp + $cache->lifetime) - time();
					$canonical               = $cache->extras['canonical'];
					$header                  = $cache->extras['header'];
					$header['Date']          = array(gmdate('D, d M Y H:i:s', time()) . ' GMT');
					$header['Cache-Control'] = array('public', 'max-age=' . $maxage, 's-maxage=' . $maxage, 'must-revalidate', 'proxy-revalidate');

					if((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $cache->extras['etag']) || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === gmdate('D, d M Y H:i:s', $cache->timestamp) . ' GMT')) {
						$header['HTTP/1.1 304 Not Modified'] = NULL;
						$content = NULL;
					}

					$dispatcher->notify(new \Glue\Event('glue.core.output.pre'));

					$this->_output($content, $header, $canonical);

					$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

					exit;
				} else {
					$environment->set('lifetime', $settings['cache']['lifetime']);
				}
			}

			// load view gateway
			$view = $factory->load('\Glue\Gateway\View');

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
			$canonical  = $environment->get('canonical');

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

				$this->_output(NULL, $header, $canonical);

				$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

				exit;
			} else {
				$this->_output($content, $header->get(), $canonical);
			}

			// flush output and clean outputbuffer
			flush();

			while(ob_get_level() > 0) {
				ob_end_clean();
			}

			$dispatcher->notify(new \Glue\Event('glue.core.output.post'));

			// write core cache
			if($settings['cache']['@attributes']['enabled'] === true && $cacheable === true) {
				$cache
					->setLifetime(strtotime($settings['cache']['lifetime']))
					->setExtras(array('etag' => $etag, 'canonical' => $canonical, 'header' => $header->get()))
					->setData($content)
					->set();
			}

			unset($node, $cacheable, $dispatcher, $factory, $configuration, $url, $request, $routing, $server, $client, $environment, $header, $session, $settings, $compression, $cache, $view, $controller, $content, $maxage, $etag, $generation, $lifetime, $canonical);

			exit;
		}

		/**
		 * Private method for content output to client
		 *
		 * @param string $content
		 * @param array $header [optional]
		 */
		final private function _output($content, array $header = array(), $canonical) {
			$environment = $this->environment->get();

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

			if($canonical !== false && \Glue\Helper\Modifier::cleanPath($_SERVER['REQUEST_URI']) !== '/' . $canonical) {
				@header('Link: <' . $environment['url']['absolute'] . $canonical . '/>; rel="canonical"');
			}

			@header('Connection: close', true);

			echo $content;

			unset($environment, $content, $header, $canonical, $name, $values, $index, $value);
		}

		/**
		 * Magic method for retrieving values of unknown or restricted properties
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
		 * Magic method for checking the existance of unknown or restricted properties
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
		 * PSR-0 compliant autoloader
		 *
		 * @param string $classname
		 */
		final private function _autoloader($classname) {
			$base = DIRECTORY_SEPARATOR . preg_replace('+[\\\/_]+', DIRECTORY_SEPARATOR, $classname);

			if(($controller = preg_replace('+^/Glue/Controller/+', '/', $base)) !== $base) {
				$controller = strtolower($controller);
				$candidates = array(
					$this->path['local'] . DIRECTORY_SEPARATOR . '.controller' . $controller . '.php',
					$this->path['global'] . DIRECTORY_SEPARATOR . '.controller' . $controller . '.php'
				);
			} else {
				$candidates = array(
					$this->path['local'] . DIRECTORY_SEPARATOR . '.libraries' . $base . '.php',
					$this->path['global'] . DIRECTORY_SEPARATOR . '.libraries' . $base . '.php'
				);
			}

			foreach($candidates as $candidate) {
				if(is_file($candidate)) {
					require($candidate);

					if(method_exists($classname, '__once')) {
						$classname::__once();
					}

					break;
				}
			}

			unset($classname, $base, $candidates, $candidate);
		}
	}
}
