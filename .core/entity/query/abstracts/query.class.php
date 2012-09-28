<?php
namespace Glue\Object\Query\Abstracts;

/**
 * Abstract base class for all queries
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
abstract class Query extends \Glue\Abstracts\Base\Chainable implements \Glue\Object\Query\Interfaces\Query {
	/**
	 * Protected property to store table information
	 */
	protected $tables     = array();

	/**
	 * Protected property to store using information
	 */
	protected $using      = array();

	/**
	 * Protected property to store join information
	 */
	protected $joins      = array();

	/**
	 * Protected property to store column information
	 */
	protected $columns    = array();

	/**
	 * Protected property to store value information
	 */
	protected $values     = array();

	/**
	 * Protected property to store where information
	 */
	protected $where      = array();

	/**
	 * Protected property to store groupby information
	 */
	protected $groupby    = array();

	/**
	 * Protected property to store having information
	 */
	protected $having     = array();

	/**
	 * Protected property to store orderby information
	 */
	protected $orderby    = array();

	/**
	 * Protected property to store limit information
	 */
	protected $limit      = NULL;

	/**
	 * Protected property to store offset information
	 */
	protected $offset     = NULL;

	/**
	 * Sets a table
	 * 
	 * @param mixed $tables
	 * @param string $hints [optional]
	 * 
	 * @return object
	 */
	protected function _table($tables, $hints = NULL) {
		$this->tables = array();
		
		return $this->_addTable($tables, $hints);
	}

	/**
	 * Adds a table
	 *
	 * @param mixed $tables
	 * @param string $hints [optional]
	 *
	 * @return object
	 */
	protected function _addTable($tables, $hints = NULL) {
		$tables = (array) $tables;
		$hints  = (array) $hints;
		
		foreach($tables as $key => $table) {
			$this->tables[] = trim(preg_replace('/,\s*$/', '', $table));
			
			if(isset($hints[$key])) {
				$this->tables[count($this->tables) - 1] .= ' ' . $hints[$key];
			}
		}

		return $this;
	}

	/**
	 * Sets using-clause to table(s)
	 *
	 * @param mixed $using
	 *
	 * @return object
	 */
	protected function _using($using) {
		$this->using = array();
		
		return $this->_addUsing($using);
	}

	/**
	 * Adds table(s) to using-clause
	 *
	 * @param mixed $using
	 *
	 * @return object
	 */
	protected function _addUsing($using) {
		$using = (array) $using;
		
		foreach($using as $use) {
			$this->using[] = trim(preg_replace('/,\s*$/', '', $use));
		}

		return $this;
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
	protected function _join($type, $table, $condition = NULL, $parameters = NULL) {
		$this->joins = array();
		
		return $this->_addJoin($type, $table, $condition, $parameters);
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
	protected function _addJoin($type, $table, $condition = NULL, $parameters = NULL) {
		if(preg_match_all('/^([\w*]+(?:\(.+?\))?)(?:\s+(?:AS\s+)?([\w]+))?$/i', $table, $table, PREG_SET_ORDER)) {
			$data             = new \stdClass();
			$data->type       = $type;
			$data->table      = $table[0][1];
			$data->alias      = (isset($table[0][2]) && !empty($table[0][2])) ? $table[0][2] : NULL;
			$data->condition  = $condition;
			$data->parameters = $parameters;
					
			$this->joins[] = $data;
			unset($data);
		}
		
		return $this;
	}

	/**
	 * Sets a column or columns
	 *
	 * @param string $columns
	 *
	 * @return object
	 */
	protected function _column($columns) {
		$this->columns = array();
		
		return $this->_addColumn($columns);
	}

	/**
	 * adds a column or columns
	 *
	 * @param string $columns
	 *
	 * @return object
	 */
	protected function _addColumn($columns) {
		$columns = (array) $columns;
		
		foreach($columns as $column) {
			$this->columns[] = trim(preg_replace('/,\s*$/', '', $column));
		}
		
		return $this;
	}

	/**
	 * Sets values
	 *
	 * @param array $values
	 *
	 * @return object
	 */
	protected function _value(array $values) {
		$this->values = array();
		
		return $this->_addValue($values);
	}

	/**
	 * Adds values
	 *
	 * @param array $values
	 *
	 * @return object
	 */
	protected function _addValue(array $values) {
		foreach($values as $column => $value) {
			$this->values[$column] = $value;
		}
		
		return $this;
	}

	/**
	 * Sets a condition
	 *
	 * @param string $type
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object

	 */
	protected function _condition($type, $condition, $parameters = NULL, $operator = 'AND') {
		$this->$type = array();
		
		return $this->_addCondition($type, $condition, $parameters, $operator);
	}


	/**
	 * Adds a condition
	 *
	 * @param string $type
	 * @param string $condition
	 * @param array $parameters [optional]
	 * @param string $operator [optional]
	 *
	 * @return object
	 */
	protected function _addCondition($type, $condition, $parameters = NULL, $operator = 'AND') {
		$data             = new \stdClass();
		$data->condition  = $condition;
		$data->operator   = $operator;
		$data->parameters = $parameters;
		
		$pointer   =& $this->$type;
		$pointer[] =  $data;
		unset($data);
		unset($pointer);
		
		return $this;
	}

	/**
	 * Sets groupby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	protected function _groupby($columns) {
		$this->groupby = array();
		
		return $this->_addGroupby($columns);
	}

	/**
	 * Adds groupby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	protected function _addGroupby($columns) {
		$columns = (array) $columns;
		
		foreach($columns as $column) {
			$this->groupby[] = trim(preg_replace('/,\s*$/', '', $column));
		}
		
		return $this;
	}

	/**
	 * Sets orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	protected function _orderby($columns) {
		$this->orderby = array();
		
		return $this->_addOrderby($columns);
	}

	/**
	 * Adds orderby-clause
	 *
	 * @param mixed $columns
	 *
	 * @return object
	 */
	protected function _addOrderby($columns) {
		$columns = (array) $columns;
		
		foreach($columns as $column) {
			$this->orderby[] = trim(preg_replace('/,\s*$/', '', $column));
		}
		
		return $this;
	}

	/**
	 * Sets limit and offset
	 *
	 * @param int $limit
	 * @param int $offset [optional]
	 *
	 * @return object
	 */
	protected function _limit($limit, $offset = 0) {
		$this->limit  = (int) $limit;
		$this->offset = (int) $offset;
		
		return $this;
	}
}
?>