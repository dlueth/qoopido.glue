<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     age.php
 * Purpose:  creates a clean URL
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2009-06-19 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_age extends Dwoo_Block_Plugin {
	public function init() {
	}

	public function process(){ 
		return \Glue\Helper\Datetime::getAge($this->buffer);
	} 
}
?>