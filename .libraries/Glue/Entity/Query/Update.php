<?php
namespace Glue\Entity\Query;

/**
 * Class for building update queries
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Update extends \Glue\Entity\Query\Abstracts\Query {
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
	 *
	 * @return object
	 */
	final public function in($tables) {
		return $this->_table($tables);
	}

	/**
	 * Adds a table
	 *
	 * @param mixed $tables
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 */
	final public function addIn($tables) {
		if(count($this->tables) > 0 && ($this->limit !== NULL || count($this->orderby) > 0)) {
			throw new \LogicException('"' . __METHOD__ . '" is only allowed if no limit and order parameters are set');
		}

		return $this->_addTable($tables);
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
	 * Sets orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 */
	final public function orderby($columns) {
		if(count($this->tables) > 1) {
			throw new \LogicException('"' . __METHOD__ . '" is only allowed for single-table updates');
		}

		return $this->_orderby($columns);
	}

	/**
	 * Adds orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 */
	final public function addOrderby($columns) {
		if(count($this->tables) > 1) {
			throw new \LogicException('"' . __METHOD__ . '" is only allowed for single-table updates');
		}

		return $this->_addOrderby($columns);
	}

	/**
	 * Sets limit and offset
	 *
	 * @param int $limit
	 * @param int $offset [optional]
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 */
	final public function limit($limit, $offset = 0) {
		if(count($this->tables) > 1) {
			throw new \LogicException('"' . __METHOD__ . '" is only allowed for single-table updates');
		}

		return $this->_limit($limit, $offset);
	}

	/**
	 * Builds and returns an update query
	 *
	 * @return object
	 */
	final public function build() {
		$return           = new \stdClass();
		$return->sql      = array('UPDATE');
		$return->bindings = array();
		
		// process tables
			$return->sql[] = implode(', ', $this->tables);
		
		// process columns
			$return->sql[] = 'SET ' . implode(' = :{BINDING}, ', array_keys($this->values)) . ' = :{BINDING}';
			unset($columns);
		
		// process values
			$return->bindings = $this->values;
		
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
		
		// process orderby
			if(count($this->orderby) > 0) {
				$return->sql[] = 'ORDER BY ' . implode(', ', $this->orderby);
			}
		
		// process limit
			if($this->limit !== NULL) {
				$return->sql[] = 'LIMIT ' . $this->limit;
			}
		
		$return->sql = implode(' ', $return->sql);
		$return->sql = preg_replace('/(([^\s]+) = :{BINDING})/', '\2 = :\2', $return->sql);
		
		foreach($return->bindings as $binding => $parameter) {
			if(is_object($parameter) && get_class($parameter) === 'Glue\Entity\Query\Expression') {
				$return->sql = preg_replace('/:' . $binding . '(?!\w)/', (string) $parameter, $return->sql);
				unset($return->bindings[$binding]);
			}
		}

		return $return;
	}
}
