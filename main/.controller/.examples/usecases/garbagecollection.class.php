<?php
namespace Glue\Controller\Usecases;

class Garbagecollection extends \Glue\Controller\General {
	protected function __initialize() {
		parent::__initialize();

		// initialize garbage collection
			$this->factory->load('\Glue\Module\Garbagecollection');
	}
}
?>