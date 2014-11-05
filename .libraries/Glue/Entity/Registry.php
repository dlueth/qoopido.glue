<?php
namespace Glue\Entity;

/**
 * Entity providing registry capabilities
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Registry {
	const PERMISSION_OWNER             = 0;
	const PERMISSION_EXISTS            = 1;
	const PERMISSION_GET               = 2;
	const PERMISSION_GETREFERENCE      = 4;
	const PERMISSION_REGISTER          = 8;
	const PERMISSION_REGISTERREFERENCE = 16;
	const PERMISSION_SET               = 32;
	const PERMISSION_SETREFERENCE      = 64;
	const PERMISSION_UNREGISTER        = 128;
	const PERMISSION_READ              = 3;
	const PERMISSION_WRITE             = 171;
	const PERMISSION_ALL               = 255;

	/**
	 * Property to store registry data
	 */
	private $data        = array();

	/**
	 * Property to store parent class
	 */
	private $parent      = NULL;

	/**
	 * Property to store permissions
	 */
	private $permissions = 0;

	/**
	 * Closure to get caller
	 */
	private static $_closureGetCaller = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			if(PHP_VERSION < '5.3.6') {
				self::$_closureGetCaller = function() {
					$return    = false;
					$backtrace = debug_backtrace();

					if(isset($backtrace[3]['object'])) {
						$return =& $backtrace[3]['object'];
					}

					unset($backtrace);

					return $return;
				};
			} elseif(PHP_VERSION < '5.4.0') {
				self::$_closureGetCaller = function() {
					$return    = false;
					$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);

					if(isset($backtrace[3]['object'])) {
						$return =& $backtrace[3]['object'];
					}

					unset($backtrace);

					return $return;
				};
			} else {
				self::$_closureGetCaller = function() {
					$return    = false;
					$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 4);

					if(isset($backtrace[3]['object'])) {
						$return =& $backtrace[3]['object'];
					}

					unset($backtrace);

					return $return;
				};
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @param object $parent
	 * @param mixed $permissions [optional]
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function __construct(&$parent, $permissions = 0) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$parent'      => array($parent, 'isObject'),
			'$permissions' => array($permissions, 'isInteger', array('matchesBitmask', array(self::PERMISSION_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->parent =& $parent;

			if($permissions !== 0) {
				$this->_setPermissions($permissions);
			}

			unset($parent, $permissions, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to check existance of a node
	 *
	 * @param string $node
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function exists($node) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$node' => array($node, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		if($this->_checkPermission(self::PERMISSION_EXISTS) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$return = $this->_getNode($node) !== NULL;

			unset($node, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to fetch a node
	 *
	 * @param string $node [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function get($node = NULL) {
		if($this->_checkPermission(self::PERMISSION_GET) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			return $this->_getNode($node);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to fetch a node by reference
	 *
	 * @param string $node [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function &getReference($node = NULL) {
		if($this->_checkPermission(self::PERMISSION_GETREFERENCE) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			return $this->_getNode($node);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to register a node
	 *
	 * @param string $node
	 * @param mixed $value [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function register($node, $value = NULL) {
		if($this->_checkPermission(self::PERMISSION_REGISTER) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$return  =  true;
			$nodes   =  $this->splitNode($node);
			$limit   =  count($nodes) - 1;
			$pointer =& $this->data;

			foreach($nodes as $index => $node) {
				if($index < $limit) {
					if(!array_key_exists($node, $pointer)) {
						$pointer[$node] = array();
					}

					$pointer =& $pointer[$node];
				}
			}

			if(!array_key_exists(end($nodes), $pointer)) {
				$pointer[end($nodes)] = $value;

				$return = $pointer[end($nodes)];
			}

			unset($node, $value, $nodes, $limit, $pointer, $index);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to register a node by reference
	 *
	 * @param string $node
	 * @param mixed $value [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function &registerReference($node, &$value = NULL) {
		if($this->_checkPermission(self::PERMISSION_REGISTERREFERENCE) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$return  =  true;
			$nodes   =  $this->splitNode($node);
			$limit   =  count($nodes) - 1;
			$pointer =& $this->data;

			foreach($nodes as $index => $node) {
				if($index < $limit) {
					if(!array_key_exists($node, $pointer)) {
						$pointer[$node] = array();
					}

					$pointer =& $pointer[$node];
				}
			}

			if(!array_key_exists(end($nodes), $pointer)) {
				$pointer[end($nodes)] =& $value;

				$return =& $pointer[end($nodes)];
			}

			unset($node, $value, $nodes, $limit, $pointer, $index);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to set a node
	 *
	 * @param string $node
	 * @param mixed $value [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function set($node, $value) {
		if($this->_checkPermission(self::PERMISSION_SET) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$return  = true;
			$pointer =& $this->data;

			if(!empty($node)) {
				$nodes = $this->splitNode($node);

				foreach($nodes as $node) {
					if(array_key_exists($node, $pointer)) {
						$pointer =& $pointer[$node];
					} else {
						$return = false;
						break;
					}
				}
			}

			$pointer = $value;

			unset($node, $value, $nodes, $pointer);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to set a node by reference
	 *
	 * @param string $node
	 * @param mixed $value [optional]
	 *
	 * @return mixed
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function setReference($node, &$value) {
		if($this->_checkPermission(self::PERMISSION_SETREFERENCE) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$return  = true;
			$pointer =& $this->data;

			if(!empty($node)) {
				$nodes   =  $this->splitNode($node);
				$limit   = count($nodes) - 1;

				foreach($nodes as $index => $node) {
					if(array_key_exists($node, $pointer)) {
						if($index < $limit) {
							$pointer =& $pointer[$node];
						}
					} else {
						$return = false;
						break;
					}
				}

				unset($nodes, $limit, $index);
			}

			if(!empty($node)) {
				$pointer[end($nodes)] =& $value;
			} else {
				$pointer =& $value;
			}

			unset($node, $value, $pointer);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to unregister a node
	 *
	 * @param string $node [optional]
	 *
	 * @return bool
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	final public function unregister($node = NULL) {
		if($this->_checkPermission(self::PERMISSION_UNREGISTER) !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_PERMISSIONS));
		}

		try {
			$parent = $this->splitNode($node);
			$node   = array_pop($parent);
			$parent = &$this->_getNode(implode('.', $parent));

			if(array_key_exists($node, $parent)) {
				unset($parent[$node]);
			}

			unset($node, $parent);

			return true;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to split a node
	 *
	 * @param string $node
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function splitNode($node) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$node' => array($node, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = preg_split('/[.\\\\\/]/', preg_replace('/^(\\\)?Glue\\\/', '', $node));

			unset($node, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}


	/**
	 * Method to set permissions
	 *
	 * @param array $permissions
	 *
	 * @throw \InvalidArgumentException
	 */
	final private function _setPermissions($permissions) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$permissions' => array($permissions, 'isInteger', array('matchesBitmask', array(self::PERMISSION_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		$this->permissions = $permissions;

		unset($permissions, $result);
	}

	/**
	 * Method to check permissions
	 *
	 * @param int $permission
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	final private function _checkPermission($permission) {
		try {
			$return     = false;
			$permission = (int) $permission;

			if(($this->permissions & $permission) !== $permission) {
				$closure = self::$_closureGetCaller;

				if($closure !== false) {
					$caller = $closure();

					if($caller === $this->parent) {
						$return = true;
					}
				}
			} else {
				$return = true;
			}

			unset($permission);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get a pointer to a node
	 *
	 * @param string $node [optional]
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final private function &_getNode($node = NULL) {
		try {
			$return = NULL;

			if($node !== NULL) {
				$pointer =& $this->data;
				$nodes   =  $this->splitNode($node);
				$limit   = count($nodes) - 1;

				foreach($nodes as $index => $node) {
					if($index < $limit) {
						if(isset($pointer[$node])) {
							$pointer =& $pointer[$node];
						} else {
							unset($pointer);
							$pointer = NULL;
							break;
						}
					}
				}

				if($pointer !== NULL && isset($pointer[end($nodes)])) {
					$return =& $pointer[end($nodes)];
				}

				unset($nodes, $limit, $pointer);
			} else {
				$return =& $this->data;
			}

			unset($node);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
