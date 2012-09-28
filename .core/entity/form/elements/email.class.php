<?php
namespace Glue\Object\Form\Elements;

/**
 * form element email
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Email extends \Glue\Object\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Object\Form &$form) {
		call_user_func_array('parent::__construct', func_get_args());

		$this->addValidator('isEmail');
	}
}
?>