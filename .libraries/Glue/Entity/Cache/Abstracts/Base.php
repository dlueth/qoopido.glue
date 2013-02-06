<?php
namespace Glue\Entity\Cache\Abstracts;

/**
 * Abstract cache class
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
abstract class Base extends \Glue\Abstracts\Base\Chainable {
	/**
	 * Property to store cache id
	 */
	protected $cid = NULL;

	/**
	 * Property to store lifetime
	 */
	protected $lifetime = NULL;

	/**
	 * Property to influence behaviour in condition of browser reload
	 */
	protected $reload = false;

	/**
	 * Property to store comparator
	 */
	protected $comparator = NULL;

	/**
	 * Property to store extras
	 */
	protected $extras = NULL;

	/**
	 * Property to store data
	 */
	protected $data = NULL;

	/**
	 * Property to store timestamp
	 */
	protected $timestamp = NULL;

	/**
	 * Property to store dependencies
	 */
	protected $dependencies = NULL;

	/**
	 * Properties storing event names
	 */
	protected $eventHit  = NULL;
	protected $eventMiss = NULL;

	/**
	 * Class constructor
	 *
	 * @param string $cid
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function __initialize($cid) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$cid' => array($cid, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->cid       = $cid;
			$this->eventHit  = preg_replace('/\.(\w+)$/', '.hit.\1', $this->id);
			$this->eventMiss = preg_replace('/\.(\w+)$/', '.miss.\1', $this->id);

			unset($result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Magic method to retrieve restricted properties
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property) {
		if(isset($this->$property)) {
			switch($property) {
				case 'dependencies':
					return $this->dependencies['external'];
					break;
				case 'cid':
					return $this->cid;
					break;
				case 'lifetime':
					return $this->lifetime;
					break;
				case 'timestamp':
					return $this->timestamp;
					break;
				case 'extras':
					return $this->extras;
					break;
			}
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
			case 'dependencies';
			case 'cid';
			case 'lifetime';
			case 'timestamp';
			case 'extras':
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Method used to set lifetime
	 *
	 * @param string $lifetime [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setLifetime($lifetime = INF) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$lifetime' => array($lifetime, 'isInteger', array('isGreater', array(0)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			if($lifetime !== INF) {
				$this->lifetime = $lifetime - time();
			} else {
				$this->lifetime = NULL;
			}

			unset($lifetime, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set reload flag
	 *
	 * @param bool $status
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setReload($status) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$status' => array($status, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->reload = $status;

			unset($status, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set dependencies
	 *
	 * @param mixed $dependencies [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setDependencies($dependencies = array()) {
		$dependencies = (is_string($dependencies)) ? (array) $dependencies : $dependencies;

		if(($result = \Glue\Helper\validator::batch(array(
			'@$dependencies' => array($dependencies, 'isPathValid')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			if(count($dependencies) > 0) {
				$this->dependencies = array(
					'external' => $dependencies,
					'internal' => array()
				);

				foreach($dependencies as $dependency) {
					$id = sha1($dependency);

					$this->dependencies['internal'][$id] = array(
						'file' => $dependency,
						'time' => @filemtime($dependency)
					);

					unset($id);
				}

				unset($dependency);
			} else {
				$this->dependencies = NULL;
			}

			unset($dependencies, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set comparator
	 *
	 * @param mixed $comparator [optional]
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	final public function setComparator($comparator = NULL) {
		try {
			if($comparator !== NULL) {
				if(!is_scalar($comparator)) {
					$comparator = base64_encode(serialize($comparator));
				}

				$comparator = trim($comparator);

				if(!empty($comparator)) {
					$this->comparator = $comparator;
				} else {
					$this->comparator = NULL;
				}
			} else {
				$this->comparator = NULL;
			}

			unset($comparator);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set extras
	 *
	 * @param mixed $extras [optional]
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	final public function setExtras($extras = NULL) {
		try {
			if($extras !== NULL) {
				$this->extras = $extras;
			} else {
				$this->extras = NULL;
			}

			unset($extras);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set data
	 *
	 * @param mixed $data
	 *
	 * @return object
	 */
	final public function setData($data) {
		$this->data = $data;

		unset($data);

		return $this;
	}

	/**
	 * Method used to retrieve cache content
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final public function get() {
		try {
			$return = false;

			if(($data = $this->_get()) !== false) {
				$return = true;

				// check reload
				if($data['status']['reload'] === true && \Glue\Component\Factory::getInstance()->exists('\Glue\Component\Client') === true && \Glue\Component\Client::getInstance()->get('reload') === true) {
					$return = false;
				}

				// check lifetime
				if($return === true && $data['status']['lifetime'] !== NULL && $data['status']['timestamp'] + $data['status']['lifetime'] < time()) {
					$return = false;
				}

				// check comparator
				if($return === true && $data['status']['comparator'] !== NULL && $this->comparator !== $data['status']['comparator']) {
					$return = false;
				}

				// check if dependencies were modified
				if($return === true && $data['status']['dependencies'] !== NULL) {
					foreach($data['status']['dependencies'] as $dependency) {
						if($dependency['time'] != @filemtime($dependency['file'])) {
							$return = false;
							break;
						}
					}

					unset($dependency);
				}

				$this->timestamp = $data['status']['timestamp'];
				$this->lifetime  = $data['status']['lifetime'];
				$this->extras    = $data['status']['extras'];
				$this->data      = $data['content'];

				if($return === true) {
					$return = $this->data;
				} else {
					$this->_clear();
				}
			}

			if($return !== false) {
				$this->dispatcher->notify(new \Glue\Event($this->eventHit, array(&$this->cid, &$return)));
			} else {
				$this->dispatcher->notify(new \Glue\Event($this->eventMiss, array(&$this->cid)));
			}

			unset($data);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set/create/save a cache
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	final public function set() {
		try {
			$this->timestamp = time();

			if(!$this->_set(array(
						'status' => array(
							'timestamp'    => $this->timestamp,
							'lifetime'     => $this->lifetime,
							'reload'       => $this->reload,
							'dependencies' => $this->dependencies['internal'],
							'comparator'   => $this->comparator,
							'extras'       => $this->extras
						),
						'content' => $this->data
					)
				)
			) {
				$this->timestamp = NULL;
				$this->_clear();
			}

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to clear a cache
	 *
	 * Caution: Suppresses exceptions on purpose
	 *
	 * @return object
	 */
	final public function clear() {
		try {
			$this->_clear();

			return $this;
		} catch(\Exception $exception) {  }
	}

	/**
	 * Method used to get a cache
	 *
	 * @return mixed
	 */
	abstract protected function _get();

	/**
	 * Method used to set a cache
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	abstract protected function _set(array $data);

	/**
	 * Method used to clear a cache
	 *
	 * @return bool
	 */
	abstract protected function _clear();
}
