<?php
namespace Glue\Controller\Usecases;

class Querybuilder extends \Glue\Controller\General {
	protected function __initialize() {
		parent::__initialize();

		// a rather complex select query
			$query = \Glue\Objects\Query\Select::getInstance()
				->addColumn('id, category, title, abstract, published')
				->addFrom('news')
				->addWhere('category = :category', array('category' => 25))
				->addOrderby('published DESC')
				->limit(25);

		// another (simple) query
			$subquery = \Glue\Objects\Query\Select::getInstance()
				->addColumn('id')
				->addFrom('published')
				->addWhere('published = :state', array('state' => true));

		// add the second query as subquery to the first one
			$query->addWhere('id IN (:subquery)', array('subquery' => $subquery));

		// an update query using an expression
			$query = \Glue\Objects\Query\Update::getInstance()
				->addValue(array('views' => new \Glue\Objects\Query\Expression('(views + 1)')))
				->in('news')
				->addWhere('id = :id', array('id' => 5));

		// build query
			$query = $query->build();

		// initialize database
			$database = $this->factory->load('\Glue\Modules\Database');

		// execute the query
			$result = $database->execute($query->sql, $query->bindings);
	}
}
?>