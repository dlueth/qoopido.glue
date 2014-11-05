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
	private $path, $prefix, $replacements;

	public function init($domain, $replacements = NULL) {
		$this->path         = \Glue\Component\Environment::getInstance()->get('path');
		$this->prefix       = $domain;
		$this->replacements = (!empty($replacements)) ? explode(',', $replacements) : array();

		foreach($this->replacements as $index => $entry) {
			$entry = explode('=', $entry);

			$this->replacements[$index] = array(
				preg_quote($entry[0], '/'),
				$entry[1]
			);
		}
	}

	public function process(){
		$return = preg_replace_callback('/((?:src|href|content|data-main)=["\'])(.+?)(["\'])/i', array($this, '_processResource'), $this->buffer);
		$return = preg_replace_callback('/(url\s*\(\s*["\']{0,1})(.+?)(["\']{0,1}\s*\))/i', array($this, '_processResource'), $return);

		return $return;
	}

	private function _processResource($matches) {
		$return = $matches[0];
		$prefix = $matches[1];
		$path   = $this->path['global'] . '/' . $matches[2];
		$suffix = $matches[3];

		/*
		if(is_file($path) && ($mtime  = @filemtime($path)) !== false) {
			$path   = \Glue\Helper\Url::make($path);

			$path = preg_replace('/^(.+)\.([\w\d]+)$/', $this->prefix . '\1.' . $mtime . '.\2', $path);

			$return = $prefix . $path . $suffix;
		}
		*/

		//if(is_file($path)) {
			$path   = preg_replace('/\/$/', '', \Glue\Helper\Url::make($path));
			$return = $prefix . $this->prefix . $path . $suffix;
		//}

		foreach($this->replacements as $index => $entry) {
			$return = preg_replace('/' . $entry[0] . '/i', $entry[1], $return);
		}

		return $return;
	}
}
