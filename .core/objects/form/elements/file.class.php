<?php
namespace Glue\Objects\Form\Elements;

/**
 * form element file
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class File extends \Glue\Objects\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Objects\Form &$form) {
		call_user_func_array('parent::__construct', func_get_args());

		$this->value = \Glue\Components\Request::getInstance()->get('files.' . $this->form->id . '.' . $this->id);
		$this->sent  = ($this->value === NULL) ? false : true;

		if($this->value === NULL) {
			$this->value = \Glue\Components\Request::getInstance()->register($this->form->method . '.' . $this->form->id . '.' . $this->id, NULL);
		} else {
			$this->addValidator('isFileUpload');
			$this->addValidator('isFileSize');
		}
	}

	/**
	 * Magic mathod to set restricted properties
	 *
	 * @param string $property
	 * @param object $value
	 */
	public function __set($property, \Glue\Objects\File $value) {
		switch($property) {
			case 'value':
				$this->valid = NULL;
				$this->value = $value;
				break;
		}
	}
}
?>