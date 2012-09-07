<?php
namespace Glue\Controller\Usecases;

class Forms extends \Glue\Controller\General {
	protected function __initialize() {
		parent::__initialize();

		//echo '=> ' . $this->environment->get('path.local') . '<br />';
		//die();

		// initialize a new form (parameters: id, method)
			$form = new \Glue\Objects\Form('example', 'post');

		// populate the form with some fields (parameters: id, type, required)
			$firstname = $form->register('firstname', 'textfield', true)
				->addModifier('ucWords');

			$lastname  = $form->register('lastname', 'textfield', true)
				->addModifier('ucWords');

			$nickname  = $form->register('nickname', 'textfield', true)
				->addModifier('sluggify');

			$phone     = $form->register('phone', 'textfield');
			$email1    = $form->register('email1', 'email', true);

			$email2    = $form->register('email2', 'email', true)
				->addValidator('isEqual', $email1->value);

		// process the form
			if($form->sent === true && $form->valid === true) {
			}

		// register the form with the view to allow access from within the template
			$this->view->register('example', $form);
	}
}
?>