<?php
namespace Glue\Objects\Form\Elements;

/**
 * form element email
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Email extends \Glue\Objects\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Objects\Form &$form) {
		call_user_func_array('parent::__construct', func_get_args());

		$this->addValidator('isEmail');
	}
}
?>