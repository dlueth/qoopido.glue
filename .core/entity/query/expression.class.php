<?php
namespace Glue\Entity\Query;

/**
 * Class for query expressions
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
final class Expression {
	/**
	 * Private property to store the expression value
	 *
	 * @string
	 */
	private $value = NULL;

	/**
	 * Class constructor
	 *
	 * @param string $value
	 */
	public function __construct($value) {
		$this->value = (string) $value;
	}

	/**
	 * Magic method to retrieve the expression value
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->value;
	}
}
?>