<?php
namespace Glue\Objects\Query;

/**
 * Class for building truncate queries
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
final class Truncate extends \Glue\Objects\Query\Abstracts\Query {
	/**
	 * Sets a table
	 *
	 * @param string $table
	 *
	 * @return object
	 */
	final public function table($table) {
		return $this->_table($table);
	}
	
	/**
	 * Builds and returns a truncate query
	 *
	 * @return object
	 */
	final public function build() {
		$return           = new \stdClass();
		$return->sql      = array('TRUNCATE');
		$return->bindings = array();
		
		// process tables
			$return->sql[] = implode(', ', $this->tables);
		
		$return->sql = implode(' ', $return->sql);
		
		return $return;
	}
}
?>