<?php
namespace Glue\Object\Form\Elements;

/**
 * form element date
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Date extends \Glue\Object\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Object\Form &$form) {
		call_user_func_array(array('parent', '__construct'), func_get_args());

		$this->addValidator('isDate');
	}
}
?>