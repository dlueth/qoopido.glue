<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.uglify.php
 * Purpose:  compresses and caches js
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-12-15 Initial release
 * -------------------------------------------------------------
 */
function smarty_block_uglify($parameters, $buffer, Smarty_Internal_Template $template, &$repeat) {
	if(!$repeat && isset($buffer)){
		static $path = NULL;
		static $url  = NULL;

		if($path === NULL) {
			$path = \Glue\Components\Environment::getInstance()->get('path');
		}

		if($url === NULL) {
			$url = \Glue\Components\Environment::getInstance()->get('url');
		}

		$files       = array();
		$source      = array();
		$return      = array();

		if(preg_match_all('/<script.+src="(.+?)".+>/i', $buffer, $results) != false) {
			foreach($results[0] as $index => $tag) {
				if(preg_match('/type="text\/javascript"/i', $tag) != false) {
					$files[] = $path['global'] . '/' . $results[1][$index];
				}
			}
		}

		if(count($files) > 0) {
			$cache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/uglify/' . sha1(implode(';', $files)) . '.js')
				->setMode('raw')
				->setDependencies($files);

			if($cache->get() === false) {
				foreach($files as $file) {
					$fcache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/uglify/.src/' . sha1($file) . '.js')
						->setMode('raw')
						->setDependencies($file);

					if(($data = $fcache->get()) === false) {
						$data = \Glue\Helper\Uglify::compress(file_get_contents($file));

						$fcache->setData($data)->set();
					}

					$source[] = $data;

					unset($fcache);
					unset($data);
				}

				$cache->setData(implode(chr(10), $source))->set();
			}

			$return[] = '<script type="text/javascript" src="' . \Glue\Helper\Url::make($cache->cid) . '"></script>';

			unset($cache);
		}

		unset($files);
		unset($source);
		unset($results);

		return implode(chr(10), $return);
	}
}
?>