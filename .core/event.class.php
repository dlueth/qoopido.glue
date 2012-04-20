<?php
namespace Glue;

/**
 * Centralized event object
 *
 * @author Dirk Lüth <dirk@qoopido.de>
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
	 *
	 * @throw \LogicException
	 */
	final public function __get($property) {
		if(isset($this->$property)) {
			return $this->$property;
		}
	}
}
?>