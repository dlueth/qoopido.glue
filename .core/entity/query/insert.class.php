<?php
namespace Glue\Entity\Query;

/**
 * Class for building insert queries
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
final class Insert extends \Glue\Entity\Query\Abstracts\Query {
	/**
	 * Sets values
	 *
	 * @param array $values
	 *
	 * @return object
	 */
	final public function value(array $values) {
		return $this->_value($values);
	}

	/**
	 * Adds values
	 *
	 * @param array $values
	 *
	 * @return object
	 */
	final public function addValue(array $values) {
		return $this->_addValue($values);
	}

	/**
	 * Sets a table
	 *
	 * @param mixed $tables
	 * @param string $hints [optional]
	 *
	 * @return object
	 */
	final public function into($tables, $hints = NULL) {
		return $this->_table($tables, $hints);
	}

	/**
	 * Adds a table
	 *
	 * @param mixed $tables
	 * @param string $hints [optional]
	 *
	 * @return object
	 */
	final public function addInto($tables, $hints = NULL) {
		return $this->_addTable($tables, $hints);
	}

	/**
	 * Builds and returns an insert query
	 *
	 * @return object
	 */
	final public function build() {
		$return           = new \stdClass();
		$return->sql      = array('INSERT');
		$return->bindings = array();
		
		// process tables
			$return->sql[] = 'INTO ' . implode(', ', $this->tables);
		
		// process columns
			$columns         = (!isset($this->values[0])) ? array_keys($this->values) : array_keys($this->values[0]);
			$return->sql[] = '(' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')';
			unset($columns);
		
		// process values
			$return->bindings = $this->values;
		
		$return->sql = implode(' ', $return->sql);
		
		foreach($return->bindings as $binding => $parameter) {
			if(is_object($parameter) && get_class($parameter) === 'Glue\Entity\Query\Expression') {
				$return->sql = preg_replace('/:' . $binding . '(?!\w)/', (string) $parameter, $return->sql);
				unset($return->bindings[$binding]);
			}
		}

		return $return;
	}
}
?>