<?php
namespace Glue\Module;

/**
 * Language module
 *
 * @event glue.module.language.load.pre(array $files) > load()
 * @event glue.module.language.load.post(array $files, array $data) > load()
 * @event glue.module.language.apply.pre(array $data) > apply()
 * @event glue.module.language.apply.post(array $data) > apply()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Language extends \Glue\Abstracts\Base {
	/**
	 * Property to provide registry
	 */
	protected $registry = NULL;

	/**
	 * Property to store path
	 */
	protected static $path = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$path = \Glue\Component\Environment::getInstance()->get('path');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 */
	protected function __initialize() {
		try {
			$this->registry = new \Glue\Entity\Registry($this, \Glue\Entity\Registry::PERMISSION_WRITE ^ \Glue\Entity\Registry::PERMISSION_UNREGISTER);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
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
	public function __call($method, $arguments) {
		if(method_exists($this->registry, $method) === true) {
			return call_user_func_array(array(&$this->registry, $method), (array) $arguments);
		}
	}

	/**
	 * Method to load language files
	 *
	 * @param mixed $language
	 * @param bool $apply [optional]
	 * @param int $scope [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function load($language, $apply = true, $scope = GLUE_SCOPE_ALL) {
		$language = (is_string($language)) ? (array) $language : $language;

		if(($result = \Glue\Helper\Validator::batch(array(
			'@$language' => array($language, 'isString'),
			'$apply'     => array($apply, 'isBoolean'),
			'$scope'     => array($scope, array('matchesBitmask', array(GLUE_SCOPE_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.pre', array($language, $apply)));

			switch($scope) {
				case GLUE_SCOPE_GLOBAL:
					$dependencies = array(
						self::$path['global'] . '/.internationalization/language/.default.xml'
					);

					foreach($language as $code) {
						$dependencies[] = self::$path['global'] . '/.internationalization/language/' . $code . '.xml';
					}
					break;
				case GLUE_SCOPE_LOCAL:
					$dependencies = array(
						self::$path['local'] . '/.internationalization/language/.default.xml'
					);

					foreach($language as $code) {
						$dependencies[] = self::$path['local'] . '/.internationalization/language/' . $code . '.xml';
					}
					break;
				case GLUE_SCOPE_ALL:
					$dependencies = array(
						self::$path['global'] . '/.internationalization/language/.default.xml',
						self::$path['local'] . '/.internationalization/language/.default.xml'
					);

					foreach(self::$path as $scope) {
						foreach($language as $code) {
							$dependencies[] = $scope . '/.internationalization/language/' . $code . '.xml';
						}
					}
					break;
			}


			$id = self::$path['local'] . '/.cache/' . __CLASS__ . '/' . sha1(serialize($language));

			if(extension_loaded('apc') === true) {
				$cache = \Glue\Entity\Cache\Apc::getInstance($id);
			} else {
				$cache = \Glue\Entity\Cache\File::getInstance($id);
			}

			$cache->setDependencies($dependencies);

			if(($data = $cache->get()) === false) {
				if(($data = \Glue\Helper\General::loadConfiguration($cache->dependencies)) !== false) {
					if(isset($data['@attributes']['characterset']) && isset($data['locales'])) {
						if(!isset($data['locales'][0])) {
							$data['locales'] = array($data['locales']);
						}

						// add characterset to locales
						foreach($data['locales'] as $index => $locale) {
							$values = (explode(',', $locale['value']));
							$value  = array();

							foreach($values as $string) {
								$value[] = trim($string) . '.' . $data['@attributes']['characterset'];
							}

							$data['locales'][$locale['locale']] = array_merge($value, $values);

							unset($data['locales'][$index], $string);
						}

						unset($index, $locale, $values, $value);
					}

					$cache->setData($data)->set();
				}
			}

			if($data !== false) {
				$this->registry->set(NULL, $data);
			}

			$this->dispatcher->notify(new \Glue\Event($this->id . '.load.post', array($language, $data)));

			if($apply === true) {
				$this->apply();
			}

			unset($language, $apply, $result, $dependencies, $id, $cache, $data);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to apply loaded languages
	 *
	 * @throw \RuntimeException
	 */
	public function apply() {
		try {
			$registry = $this->registry->get();

			$this->dispatcher->notify(new \Glue\Event($this->id . '.apply.pre', array($registry)));

			// alter environment
				if(isset($registry['@attributes']['code'])) {
					\Glue\Component\Environment::getInstance()->set('language', $registry['@attributes']['code']);
				}

				if(isset($registry['@attributes']['characterset'])) {
					\Glue\Component\Environment::getInstance()->set('characterset', $registry['@attributes']['characterset']);
				}

			// alter php.ini
				if(isset($registry['@attributes']['characterset'])) {
					ini_set('default_charset', $registry['@attributes']['characterset']);
				}

			// set system locales
				if(isset($registry['locales'])) {
					foreach($registry['locales'] as $locale => $value) {
						switch($locale) {
							case 'LC_ALL':
								setlocale(LC_ALL, $value);
								break;
							case 'LC_COLLATE':
								setlocale(LC_COLLATE, $value);
								break;
							case 'LC_CTYPE':
								setlocale(LC_CTYPE, $value);
								break;
							case 'LC_MONETARY':
								setlocale(LC_MONETARY, $value);
								break;
							case 'LC_NUMERIC':
								setlocale(LC_NUMERIC, $value);
								break;
							case 'LC_TIME':
								setlocale(LC_TIME, $value);
								break;
							case 'LC_MESSAGES':
								if(defined('LC_MESSAGES')) {
									setlocale(LC_MESSAGES, $value);
								}
								break;
						}
					}

					unset($locale, $value);
				}

			$this->dispatcher->notify(new \Glue\Event($this->id . '.apply.post', array($registry)));

			unset($registry);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
