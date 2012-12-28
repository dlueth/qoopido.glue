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
	private $path, $url, $inline, $files, $source;

	public function init() {
		$this->path        = \Glue\Component\Environment::getInstance()->get('path');
		$this->url         = \Glue\Component\Environment::getInstance()->get('url');
		$this->inline      = array();
		$this->files       = array();
		$this->source      = array();
		$this->return      = array();
	}

	public function process(){
		if(preg_match_all('/<script.*?>(.*?)<\/script>/is', $this->buffer, $results, PREG_SET_ORDER) != false) {
			foreach($results as  $element) {
				if(!empty($element[1]) && preg_match('/type="text\/javascript"/i', $element[0]) != false) {
					$this->inline[] = $element[1];
				}
			}
		}

		if(count($this->inline) > 0) {
			$this->source = array();

			$cache = \Glue\Entity\Cache\File::getInstance($this->path['local'] . '/cache/uglify/' . sha1(implode('', $this->inline)) . '.js')
				->setMode('raw');

			if(($source = $cache->get()) === false) {
				foreach($this->inline as $js) {
					$fcache = \Glue\Entity\Cache\File::getInstance($this->path['local'] . '/cache/uglify/.src/' . sha1($js) . '.js')
						->setMode('raw');

					if(($data = $fcache->get()) === false) {
						$data = \Glue\Helper\Uglify::compress($js);

						$fcache->setData($data)->set();
					}

					$this->source[] = $data;

					unset($fcache);
					unset($data);
				}

				$source = implode('', $this->source);

				$cache->setData($source)->set();
			}

			$this->return[] = '<script type="text/javascript">' . $source . '</script>';

			unset($cache, $source);
		}

		if(preg_match_all('/<script.+src="(.+?)".+>/i', $this->buffer, $results) != false) {
			foreach($results[0] as $index => $tag) {
				if(preg_match('/type="text\/javascript"/i', $tag) != false) {
					$this->files[] = $this->path['global'] . '/' . $results[1][$index];
				}
			}
		}

		if(count($this->files) > 0) {
			$this->source = array();
			$cache = \Glue\Entity\Cache\File::getInstance($this->path['local'] . '/cache/uglify/' . sha1(implode(';', $this->files)) . '.js')
				->setMode('raw')
				->setDependencies($this->files);

			if($cache->get() === false) {
				foreach($this->files as $file) {
					$fcache = \Glue\Entity\Cache\File::getInstance($this->path['local'] . '/cache/uglify/.src/' . sha1($file) . '.js')
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

				$cache->setData(implode('', $this->source))->set();
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