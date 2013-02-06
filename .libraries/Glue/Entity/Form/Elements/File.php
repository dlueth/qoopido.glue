<?php
namespace Glue\Entity\Form\Elements;

/**
 * form element file
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class File extends \Glue\Entity\Form\Abstracts\Element {
	public function __construct($id, $type, \Glue\Entity\Form &$form) {
		call_user_func_array('parent::__construct', func_get_args());

		$this->value = \Glue\Component\Request::getInstance()->get('files.' . $this->form->id . '.' . $this->id);
		$this->sent  = ($this->value === NULL) ? false : true;

		if($this->value === NULL) {
			$this->value = \Glue\Component\Request::getInstance()->register($this->form->method . '.' . $this->form->id . '.' . $this->id, NULL);
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
	public function __set($property, \Glue\Entity\File $value) {
		switch($property) {
			case 'value':
				$this->valid = NULL;
				$this->value = $value;
				break;
		}
	}
}
