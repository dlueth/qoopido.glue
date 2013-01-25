<?php
namespace Glue\Module;

/**
 * Database module
 *
 * @require PHP "PDO" extension
 *
 * @event glue.module.database.connect.pre() > connect()
 * @event glue.module.database.connect.post() > connect()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Database extends \Glue\Abstracts\Base {
	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('pdo') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'PDO'), EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Property to store configuration
	 */
	protected $configuration = NULL;

	/**
	 * Property to store PDO-Options
	 */
	protected $options = NULL;

	/**
	 * Property to store PDO-Instance
	 */
	protected $pdo = NULL;

	/**
	 * Property to store available columns
	 */
	protected $columns = array();

	/**
	 * Property to store prepared statements
	 */
	protected $statements = array();

	/**
	 * Class constructor
	 *
	 * @param array $configuration [optional]
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize($configuration = array()) {
		try {
			$this->configuration = \Glue\Helper\General::merge((array) \Glue\Component\Configuration::getInstance()->get(__CLASS__), $configuration);

			$this->options       = array(
				\PDO::ATTR_PERSISTENT         => true,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->configuration['characterset'] . '; SET CHARACTER SET ' . $this->configuration['characterset'] . ';'
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to connect to database
	 *
	 * @throw \RuntimeException
	 */
	public function connect() {
		try {
			if($this->pdo === NULL) {
				$this->dispatcher->notify(new \Glue\Event($this->id . '.connect.pre'));


					$this->pdo = new \PDO(
						$this->configuration['type'] . ':host=' . $this->configuration['hostname'] . ';port=' . $this->configuration['port'] . ';dbname=' . $this->configuration['database'],
						$this->configuration['username'],
						$this->configuration['password'],
						$this->options
					);

				$this->dispatcher->notify(new \Glue\Event($this->id . '.connect.post'));
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to prepare a statement
	 *
	 * @param string $sql
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function statementPrepare($sql) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$sql' => array($sql, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$id = sha1($sql);

			if(!isset($this->statements[$id])) {
				$this->connect();

				$this->statements[$id] = $this->pdo->prepare($sql);

				$this->statements[$id]->_callback = 'fetchAll';
				$this->statements[$id]->_params   = \PDO::FETCH_ASSOC;

				if(preg_match('/^(insert|replace|update|delete) /i', $sql)) {
					$this->statements[$id]->_callback  = 'rowCount';
					$this->statements[$id]->_parameter = array();;
				}
			}

			$return = $this->statements[$id];

			unset($sql, $result, $id);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to execute a prepared statement
	 *
	 * @param object $statement
	 * @param array $bindings [optional]
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	public function statementExecute(\PDOStatement $statement, array $bindings = array()) {
		if(($result = \Glue\Helper\validator::batch(array(
			'@$bindings' => array($bindings, 'isScalar')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$this->connect();

			if($statement->execute($bindings) !== false) {
				return call_user_func_array(array($statement, $statement->_callback), $statement->_parameter);
			}

			$statement->closeCursor();

			unset($statement, $bindings, $result);

			return false;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to prepare and execute a query
	 *
	 * @param string $sql
	 * @param array $bindings [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function execute($sql, array $bindings = array()) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$sql'       => array($sql, 'isString'),
			'@$bindings' => array($bindings, 'isScalar')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			return $this->statementExecute($this->statementPrepare($sql), $bindings);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to retrieve last insert ID
	 *
	 * @return int
	 *
	 * @throw \RuntimeException
	 */
	public function lastInsertId() {
		try {
            $this->connect();

			return $this->pdo->lastInsertId();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to start a transaction
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public function transactionStart() {
		try {
			$this->connect();
		
			return $this->pdo->beginTransaction();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to commit a transaction
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public function transactionCommit() {
		try {
			$this->connect();
		
			return $this->pdo->commit();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to rollback a transaction
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public function transactionRollback() {
		try {
			$this->connect();

			return $this->pdo->rollback();
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to fetch all columns of a table
	 *
	 * @param string $table
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function getColumns($table) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$table'       => array($table, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
            $this->connect();

			if(!isset($this->columns[$table])) {
				switch($this->configuration['type']) {
					case 'sqlite':
						$sql = 'PRAGMA table_info("' . $table . '");';
						$key = 'name';
						break;
					case 'mysql':
						$sql = 'DESCRIBE ' . $table . ';';
						$key = 'Field';
						break;
					default:
						$sql = 'SELECT column_name FROM information_schema.columns WHERE table_name = "' . $table . '";';
						$key = 'column_name';
						break;
				}

				if(($results = $this->execute($sql)) !== false) {
					foreach($results as $k => $v) {
						$results[$k] = $v[$key];
					}

					$this->columns[$table] = $results;

					unset($results, $k, $v);
				} else {
					$this->columns[$table] = false;
				}

				unset($sql, $key);
			}

			$return = $this->columns[$table];

			unset($table, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to filter parameter to match tables' columns
	 *
	 * @param string $table
	 * @param array $parameter
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function filterParameter($table, array $parameter) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$table'       => array($table, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = array();

			if(($columns = $this->getColumns($table)) !== false) {
				$return = array_intersect_key($parameter, array_flip($columns));
			}

			unset($table, $parameter, $result, $columns);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>