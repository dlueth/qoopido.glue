<?php
namespace Glue\Controller\Usecases;

class Thumbnails extends \Glue\Controller\General {
	protected function __initialize() {
		parent::__initialize();

		$image = \Glue\Objects\Image::getInstance($this->environment->get('path.local') . '/img/lemon.jpg')
			->resize(210, 2)
			->crop(210, 200)
			->sharpen()
			->get();
	}
}
?>