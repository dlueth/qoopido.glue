<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     cdn.php
 * Purpose:  map to a cCDN domain
 *
 * Author:   Dirk Lüth <info@qoopido.com>
 * Version:  1.0.0
 * Internet: http://www.qoopido.com
 *
 * Changelog:
 * 2013-04-12 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_cdn extends Dwoo_Block_Plugin {
	private $path, $prefix;

	public function init($domain) {
		$this->path   = \Glue\Component\Environment::getInstance()->get('path');
		$this->prefix = $domain;
	}

	public function process(){
		return preg_replace_callback('/(<.+ (?:src|href)=["\'])(.+?)(["\'].*>)/i', array($this, '_processResource'), $this->buffer);
	}

	private function _processResource($matches) {
		$prefix = $matches[1];
		$path   = $this->path['global'] . '/' . $matches[2];
		$suffix = $matches[3];

		if(is_file($path) && ($mtime  = @filemtime($path)) !== false) {
			$path   = \Glue\Helper\Url::make($path);

			$path = preg_replace('/^(.+)\.([\w\d]+)$/', $this->prefix . '\1.' . $mtime . '.\2', $path);

			return $prefix . $path . $suffix;
		}

		return $matches[0];
	}
}
