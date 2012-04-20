<?php
namespace Glue\Objects\Query;

/**
 * Class for building select queries
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Select extends \Glue\Objects\Query\Abstracts\Query {
	/**
	 * Sets a column or columns
	 *
	 * @param string $columns
	 *
	 * @return object
	 */
	final public function column($columns) {
		return $this->_column($columns);
	}

	/**
	 * adds a column or columns
	 *
	 * @param string $columns
	 *
	 * @return object
	 */
	final public function addColumn($columns) {
		return $this->_addColumn($columns);
	}

	/**
	 * Sets a table
	 *
	 * @param mixed $tables
	 * @param string $hints [optional]
	 *
	 * @return object
	 */
	final public function from($tables, $hints = NULL) {
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
	final public function addFrom($tables, $hints = NULL) {
		return $this->_addTable($tables, $hints);
	}

	/**
	 * Sets a join
	 *
	 * @param string $type
	 * @param string $table
	 * @param string $condition [optional]
	 * @param array $parameters [optional]
	 *
	 * @return object
	 */
	final public function join($type, $table, $condition = NULL, $parameters = NULL) {
		return $this->_join($type, $table, $condition, $parameters);
	}

	/**
	 * Adds a join
	 *
	 * @param string $type
	 * @param string $table
	 * @param string $condition [optional]
	 * @param array $parameters [optional]
	 *
	 * @return object
	 */
	final public function addJoin($type, $table, $condition = NULL, $parameters = NULL) {
		return $this->_addJoin($type, $table, $condition, $parameters);
	}

	/**
	 * Sets a where condition
	 *
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object
	 */
	final public function where($condition, $parameters = NULL, $operator = 'AND') {
		return $this->_condition('where', $condition, $parameters, $operator);
	}


	/**
	 * Adds a where condition
	 *
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object
	 */
	final public function addWhere($condition, $parameters = NULL, $operator = 'AND') {
		return $this->_addCondition('where', $condition, $parameters, $operator);
	}

	/**
	 * Sets a Having condition
	 *
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object
	 */
	final public function having($condition, $parameters = NULL, $operator = 'AND') {
		return $this->_condition('having', $condition, $parameters, $operator);
	}


	/**
	 * Adds a Having condition
	 *
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object
	 */
	final public function addHaving($condition, $parameters = NULL, $operator = 'AND') {
		return $this->_addCondition('having', $condition, $parameters, $operator);
	}

	/**
	 * Sets groupby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	final public function groupby($columns) {
		return $this->_groupby($columns);
	}

	/**
	 * Adds groupby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	final public function addGroupby($columns) {
		return $this->_addGroupby($columns);
	}

	/**
	 * Sets orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	final public function orderby($columns) {
		return $this->_orderby($columns);
	}

	/**
	 * Adds orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	final public function addOrderby($columns) {
		return $this->_addOrderby($columns);
	}

	/**
	 * Sets limit and offset
	 *
	 * @param int $limit
	 * @param int $offset [optional]
	 *
	 * @return object
	 */
	final public function limit($limit, $offset = 0) {
		return $this->_limit($limit, $offset);
	}

	/**
	 * Builds and returns a select query
	 *
	 * @return object
	 */
	final public function build() {
		$return           = new \stdClass();
		$return->sql      = array('SELECT');
		$return->bindings = array();
		
		// process columns
			$return->sql[] = implode(', ', $this->columns);
		
		// process tables
			$return->sql[] = 'FROM ' . implode(', ', $this->tables);
		
		// process joins
			if(count($this->joins) > 0) {
				$return->sql[] = implode(' ', $this->joins);
			}
		
		// process where conditions
			if(count($this->where) > 0) {
				$conditions = false;
				
				foreach($this->where as $condition) {
					if($conditions !== false) {
						$conditions .= ' ' . $condition->operator . ' ';
					}
					
					$conditions .= $condition->condition;
					
					if(is_array($condition->parameters)) {
						foreach($condition->parameters as $id => $parameter) {
							if(is_object($parameter) && get_class($parameter) === __CLASS__) {
								$subselect  = $parameter->build();
								$parameters = array();
								
								foreach($subselect->parameters as $binding => $value) {
									$subselect->query = str_replace(':' . $binding, ':' . $id . '_' . $binding, $subselect->query);
									
									$parameters[$id . '_' . $binding] = $value;
								}
								
								$conditions = str_replace(':' . $id, $subselect->query, $conditions);
								
								$return->bindings = array_merge($return->bindings, $parameters);
								
								unset($condition->parameters[$id]);
								unset($subselect);
								unset($parameters);
							}
						}
						
						$return->bindings = array_merge($return->bindings, $condition->parameters);
					}
				}
				
				$return->sql[] = 'WHERE ' . $conditions;
			}
		
		// process groupby
			if(count($this->groupby) > 0) {
				$return->sql[] = 'GROUP BY ' . implode(', ', $this->groupby);
			}
		
		// process having conditions
			if(count($this->having) > 0) {
				$conditions = false;
				
				foreach($this->having as $condition) {
					if($conditions !== false) {
						$conditions .= $condition->operator . ' ';
					}
					
					$conditions .= $condition->condition;
					
					if(is_array($condition->parameters)) {
						$return->bindings = array_merge($return->bindings, $condition->parameters);
					}
				}
				
				$return->sql[] = 'HAVING ' . $conditions;
			}
		
		// process orderby
			if(count($this->orderby) > 0) {
				$return->sql[] = 'ORDER BY ' . implode(', ', $this->orderby);
			}
		
		// process limit and offset
			if($this->limit !== NULL) {
				$return->sql[] = 'LIMIT ' . $this->limit . ' OFFSET ' . $this->offset;
			}
		
		$return->sql = implode(' ', $return->sql);

		return $return;
	}
}
?>