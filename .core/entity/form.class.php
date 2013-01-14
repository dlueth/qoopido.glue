<?php
namespace Glue\Entity;

/**
 * Entity for form handling
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
class Form {
	/**
	 * Property to store form id
	 */
	protected $id = NULL;

	/**
	 * Property to store form method
	 */
	protected $method = NULL;

	/**
	 * Property to store validation errors
	 */
	protected $errors = NULL;

	/**
	 * Property to store valid state
	 */
	protected $valid = NULL;

	/**
	 * Property to store sent state
	 */
	protected $sent = NULL;

	/**
	 * Property to store form elements
	 */
	protected $elements = array();

	/**
	 * Property to store available modifier
	 */
	protected static $modifier = array();

	/**
	 * Property to store available validator
	 */
	protected static $validator = array();

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			$modifier  = get_class_methods('\Glue\Helper\Modifier');
			$validator = get_class_methods('\Glue\Helper\Validator');

			foreach($modifier as $name) {
				self::$modifier[$name] = '\Glue\Helper\Modifier::' . $name;
			}

			foreach($validator as $name) {
				self::$validator[$name] = '\Glue\Helper\Validator::' . $name;
			}

			unset($modifier, $validator, $name);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @param string $id
	 * @param string $method
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function __construct($id, $method) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$id'     => array($id, 'isString', 'isNotEmpty'),
			'$method' => array($method, 'isString', array('matchesPattern', array('^get|post|put|delete$')))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$this->id       = $id;
			$this->method   = $method;

			unset($id, $method, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Magic mathod to retrieve restricted properties
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property) {
		switch($property) {
			case 'id':
				return $this->id;
				break;
			case 'method':
				return $this->method;
				break;
			case 'errors':
				if($this->valid === NULL) {
					$this->validate();
				}

				if($this->errors !== NULL) {
					return $this->errors;
				}

				break;
			case 'valid':
				if($this->valid === NULL) {
					$this->validate();
				}

				return $this->valid;
				break;
			case 'sent':
				if($this->sent === NULL) {
					$this->sent = false;

					foreach($this->elements as $element) {
						//echo '=> ' . $element->name . ': ' . $element->sent . '<br />';
						if($element->sent !== false) {
							$this->sent = true;
							break;
						}
					}

					unset($element);
				}
				return $this->sent;
				break;
			case 'elements':
				return $this->elements;
				break;
			case 'validator':
				return self::$validator;
				break;
			case 'modifier':
				return self::$modifier;
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
			case 'id';
			case 'method';
			case 'errors';
			case 'valid';
			case 'sent';
			case 'elements';
			case 'validator';
			case 'modifier':
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Method to validate form
	 *
	 * @throw \RuntimeException
	 */
	public function validate() {
		try {
			foreach($this->elements as $element) {
				if($element->valid === false) {
					if(!isset($this->errors)) {
						$this->errors = Array();
					}

					$this->errors[$element->id] = $element->errors;
				}
			}

			if(isset($this->errors)) {
				$this->valid = false;
			} else {
				$this->valid = true;
			}

			unset($element);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to register elements
	 *
	 * @param string $id
	 * @param string $type [optional]
	 * @param bool $required [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function register($id, $type = 'textfield', $required = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$id'       => array($id, 'isString', 'isNotEmpty'),
			'$type'     => array($id, 'isString', 'isNotEmpty'),
			'$required' => array($required, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = false;

			if(!isset($this->elements[$id])) {
				$classname = '\Glue\Entity\Form\Elements\\' . ucfirst($type);

				$this->elements[$id] = new $classname($id, $type, $this);

				if($required === true) {
					$this->elements[$id]->addValidator('isNotEmpty');
				}

				$return =& $this->elements[$id];

				unset($classname);
			}

			unset($id, $type, $required, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>