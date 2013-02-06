<?php
namespace Glue\Entity\Form\Elements;

/**
 * form element email
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Email extends \Glue\Entity\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Entity\Form &$form) {
		call_user_func_array('parent::__construct', func_get_args());

		$this->addValidator('isEmail');
	}
}
