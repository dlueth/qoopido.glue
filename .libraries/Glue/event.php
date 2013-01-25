<?php
namespace Glue;

/**
 * Centralized event object
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Event {
	/**
	 * Private property to store event name
	 *
	 * @string
	 */
	private $name;

	/**
	 * Private property to store event parameters
	 *
	 * @array
	 */
	private $parameters = array();

	/**
	 * Class constructor
	 *
	 * @param string $name
	 * @param array $parameters [optional]
	 */
	public function __construct($name, array $parameters = array()) {
		$this->name       = $name;
		$this->parameters = $parameters;
	}

	/**
	 * Magic method for retrieving values of restricted properties
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	final public function __get($property) {
		if(isset($this->$property)) {
			return $this->$property;
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
		return isset($this->$property);
	}
}
?>