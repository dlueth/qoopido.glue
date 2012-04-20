<?php
namespace Glue\Controller\Usecases;

class Routing extends \Glue\Controller\General {
	protected function __initialize() {
		// call parent constructor
		parent::__initialize();

		$example    = $this->request->get('get.example');
		$parameters = $this->request->get('get.parameters');

		switch($example) {
			case NULL:
				$message = 'Congratulations, you directly reached this page!';
				break;
			default:
				$message = 'You reached this page via the ' . $example . ' route!';
				break;
		}

		$this->view->register('message', $message);
	}
}
?>