<?php
namespace Glue\Controller;

class General extends \Glue\Abstracts\Controller {
	protected function __initialize() {
		// initialize handling of rendering erros
			// $this->dispatcher->addListener(array(&$this, 'onRenderError'), 'glue.gateway.view.render.error');

		// initialize & register language
			$language = $this->factory->load('\Glue\Module\Language');
			$language->load($this->environment->get('language'));
			$this->view->register('language', $language->get());

		// initialize & register tree
			$tree = $this->factory->load('\Glue\Module\Tree');
			$tree->load($this->environment->get('language'));
			$this->view->register('tree', $tree->get());

		// code used in use-cases
			// process general session variable
			if($this->session->exists('data.visits') === false) {
				$this->session->register('data.visits', 0);
			}

			$this->session->set('data.visits', $this->session->get('data.visits') + 1);
	}

	/**
	 * Event listener
	 */
	public function onRenderError() {
		header('HTTP/1.0 404 Not Found');
		exit();
	}
}
?>