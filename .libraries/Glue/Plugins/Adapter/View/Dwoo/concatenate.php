<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     concatenate.php
 * Purpose:  concatenates javascript files
 *
 * Author:   Dirk Lüth <info@qoopido.com>
 * Version:  1.0.0
 * Internet: http://www.qoopido.com
 *
 * Changelog:
 * 2012-01-18 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_concatenate extends Dwoo_Block_Plugin {
	private $path, $url, $inline, $files, $source;

	public function init() {
		$this->path        = \Glue\Component\Environment::getInstance()->get('path');
		$this->url         = \Glue\Component\Environment::getInstance()->get('url');
		$this->files       = array();
		$this->source      = array();
		$this->return      = array();
	}

	public function process(){
		if(preg_match_all('/<script.+src="(.+?)".+>/i', $this->buffer, $results) != false) {
			foreach($results[0] as $index => $tag) {
				if(preg_match('/type="text\/javascript"/i', $tag) != false) {
					$this->files[] = $this->path['global'] . '/' . $results[1][$index];
				}
			}
		}

		if(count($this->files) > 0) {
			$this->source = array();
			$cache = \Glue\Entity\Cache\File::getInstance($this->path['local'] . '/cache/assets/js/' . sha1(implode(';', $this->files)) . '.js')
				->setMode('raw')
				->setDependencies($this->files);

			if($cache->get() === false) {
				foreach($this->files as $file) {
					$this->source[] = @file_get_contents($file);
				}

				$cache->setData(implode('', $this->source))->set();
			}

			$this->return[] = '<script type="text/javascript" src="' . \Glue\Helper\Url::make($cache->cid) . '"></script>';

			unset($cache);
		}

		unset($this->files, $this->source, $this->results);

		return implode(chr(10), $this->return);
	} 
}
?>