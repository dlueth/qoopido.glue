<?php
namespace Glue\Controller\Usecases;

class Sessions extends \Glue\Controller\General {
	protected function __initialize() {
		// call parent constructor
		parent::__initialize();

		// process page session variable
		if($this->session->exists('page.visits') === false) {
			$this->session->register('page.visits', 0);
		}

		$this->session->set('page.visits', $this->session->get('page.visits') + 1);
	}
}
?>