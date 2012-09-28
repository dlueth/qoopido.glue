<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     uglify.php
 * Purpose:  combines/compresses Javascript via http://marijnhaverbeke.nl/uglifyjs
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-03-23 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_uglify extends Dwoo_Block_Plugin {
	private $path, $url, $files, $source;

	public function init() {
		$this->path        = \Glue\Components\Environment::getInstance()->get('path');
		$this->url         = \Glue\Components\Environment::getInstance()->get('url');
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
			$cache = \Glue\Objects\Cache\File::getInstance($this->path['local'] . '/cache/uglify/' . sha1(implode(';', $this->files)) . '.js')
				->setMode('raw')
				->setDependencies($this->files);

			if($cache->get() === false) {
				foreach($this->files as $file) {
					$fcache = \Glue\Objects\Cache\File::getInstance($this->path['local'] . '/cache/uglify/.src/' . sha1($file) . '.js')
						->setMode('raw')
						->setDependencies($file);

					if(($data = $fcache->get()) === false) {
						$data = \Glue\Helper\Uglify::compress(file_get_contents($file));

						$fcache->setData($data)->set();
					}

					$this->source[] = $data;

					unset($fcache);
					unset($data);
				}

				$cache->setData(implode(chr(10), $this->source))->set();
			}

			$this->return[] = '<script type="text/javascript" src="' . \Glue\Helper\Url::make($cache->cid) . '"></script>';

			unset($cache);
		}

		unset($this->files);
		unset($this->source);
		unset($this->results);

		return implode(chr(10), $this->return);
	} 
}
?>