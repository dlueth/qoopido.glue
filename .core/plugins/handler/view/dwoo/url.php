<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     url.php
 * Purpose:  creates a clean URL
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2008-10-15 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_url extends Dwoo_Block_Plugin {
	private $scope;
	private $separator;
	private $anchor;
	private $parameters;

	public function init($scope = 'local', $separator = '&amp;', $anchor = NULL, array $rest = array()) {
		$this->scope       = $scope; 
		$this->separator   = $separator;
		$this->anchor      = $anchor;
		$this->parameters  = $rest;
	}

	public function process(){ 
		return \Glue\Helper\Url::make($this->buffer, $this->scope, $this->parameters, $this->anchor, $this->separator);
	}
}
?>