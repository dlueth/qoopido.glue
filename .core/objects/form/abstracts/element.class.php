<?php
namespace Glue\Objects\Form\Abstracts;

/**
 * Abstract form element class
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
abstract class Element {
	/**
	 * Property to store default value
	 */
	public    $default    = NULL;

	/**
	 * Property to store id
	 */
	protected $id         = NULL;

	/**
	 * Property to store name
	 */
	protected $name       = NULL;

	/**
	 * Property to store type
	 */
	protected $type       = NULL;

	/**
	 * Property to store value
	 */
	protected $value      = NULL;

	/**
	 * Property to store sent status
	 */
	protected $sent       = NULL;

	/**
	 * Property to store valid status
	 */
	protected $valid      = NULL;

	/**
	 * Property to store errors
	 */
	protected $errors     = NULL;

	/**
	 * Property to store form
	 */
	protected $form       = NULL;

	/**
	 * Property to store modifier
	 */
	protected $modifier   = array();

	/**
	 * Property to store validator
	 */
	protected $validator  = array();


	/**
	 * Class constructor
	 *
	 * @param string $id
	 * @param string $type
	 * @param object $form
	 */
	public function __construct($id, $type, \Glue\Objects\Form &$form) {
		$this->id    =  $id;
		$this->name  =  $form->id . '[' . $id . ']';
		$this->type  =  $type;
		$this->form  =& $form;
		$this->value =  \Glue\Components\Request::getInstance()->get($this->form->method . '.' . $this->form->id . '.' . $this->id);
		$this->sent  = ($this->value === NULL) ? false : true;

		if($this->value === NULL) {
			$this->value = \Glue\Components\Request::getInstance()->register($this->form->method . '.' . $this->form->id . '.' . $this->id, NULL);
		}
	}

	/**
	 * Method to add a modifier
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function addModifier($name) {
		$return = false;

		if(isset($this->form->modifier[$name])) {
			if(!in_array($name, $this->modifier)) {
				$this->modifier[] = $name;

				$return = true;
			}
		}

		return $return;
	}

	/**
	 * Method to add a validator
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function addValidator($name) {
		$return = false;

		if(isset($this->form->validator[$name])) {
			$arguments = func_get_args();
			array_shift($arguments);

			if(!isset($this->validator[$name])) {
				$this->validator[$name] = array();
			}

			$this->validator[$name][] = $arguments;

			$return = true;
		}

		return $return;
	}

	/**
	 * Method to validate the element
	 */
	public function validate() {
		// process modifier
			if($this->value !== NULL) {
				foreach($this->modifier as $modifier) {
					$this->value = call_user_func($this->form->modifier[$modifier], $this->value);
				}
			}

		// check validator
			foreach($this->validator as $validator => $calls) {
				foreach($calls as $arguments) {
					array_unshift($arguments, $this->value);

					if(call_user_func_array($this->form->validator[$validator], $arguments) !== true) {
						if(!isset($this->errors)) {
							$this->errors = array();
						}

						$this->errors[] = $validator;
					}
				}
			}

		// set validation status
			if($this->errors !== NULL) {
				$this->valid = false;
			} else {
				$this->valid = true;
			}
	}

	/**
	 * Method to manually set an error
	 */
	public function setError($key, $value) {
		if(!isset($this->errors[$key])) {
			$this->errors[$key] = array();
		}

		$this->errors[$key][] = $value;
		$this->valid          = false;

		$this->form->validate();
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
			case 'name':
				return $this->name;
				break;
			case 'type':
				return $this->type;
				break;
			case 'value':
				return $this->value;
				break;
			case 'sent':
				return $this->sent;
				break;
			case 'errors':
				if($this->form->sent === true && $this->valid === NULL) {
					$this->validate();
				}

				return $this->errors;
				break;
			case 'valid':
				if($this->form->sent === true && $this->valid === NULL) {
					$this->validate();
				}

				return $this->valid;
				break;
		}
	}

	/**
	 * Magic mathod to set restricted properties
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value) {
		switch($property) {
			case 'value':
				$this->valid = NULL;
				$this->value = $value;
				break;
		}
	}
}
?>