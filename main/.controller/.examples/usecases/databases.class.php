<?php
namespace Glue\Controller\Usecases;

class Databases extends \Glue\Controller\General {
	protected function __initialize() {
		parent::__initialize();

		// initialize database
			$database = $this->factory->load('\Glue\Modules\Database');

		// directly execute a query
			$result = $database->execute('SELECT id, title, abstract, published FROM news WHERE published <= :date ORDER BY published DESC LIMIT 25', array('date' => date('Y-m-d')));

		// use a prepared statement
			$statement = $database->statementPrepare('SELECT id, title, abstract, published FROM news WHERE published <= :date ORDER BY published DESC LIMIT 25');
			$result    = $database->statementExecute($statement, array('date' => date('Y-m-d')));

		// filter an associative array to match a tables columns (e.g. from a form)
			$data = $database->filterParameter('news', array(
				'title'    => '[new value for title]',
				'abstract' => '[new value for abstract]',
				'temp'     => '[non existing column]'
			));

		// other public methods
			$database->lastInsertId();
			$database->transactionStart();
			$database->transactionCommit();
			$database->transactionRollback();
			$database->getColumns('news');

		// register the result with the view to allow access from within the template
			$this->view->register('result', $result);
	}
}
?>