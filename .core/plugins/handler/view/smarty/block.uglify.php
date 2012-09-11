<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.yui.php
 * Purpose:  compresses and caches css and js
 *
 * Author:   Dirk Lüth <dlskynet@gmx.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-12-15 Initial release
 * -------------------------------------------------------------
 */
function smarty_block_yui($parameters, $buffer, Smarty_Internal_Template $template, &$repeat) {
	if(!$repeat && isset($buffer)){
		static $path = NULL;
		static $url  = NULL;

		if($path === NULL) {
			$path = \Glue\Components\Environment::getInstance()->get('path');
		}

		if($url === NULL) {
			$url = \Glue\Components\Environment::getInstance()->get('url');
		}

		$files       = new \stdClass();
		$files->css  = array();
		$files->js   = array();

		$source      = new \stdClass();
		$source->css = array();
		$source->js  = array();

		$return      = array();

		if(preg_match_all('/<link.+href="(.+?)".+>/i', $buffer, $results) != false) {
			foreach($results[0] as $index => $tag) {
				if(preg_match('/rel="stylesheet"/i', $tag) != false) {
					$files->css[] = $path['global'] . '/' . $results[1][$index];
				}
			}
		}

		if(count($files->css) > 0) {
			$cache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/yui/css/' . sha1(implode(';', $files->css)) . '.css')
				->setMode('raw')
				->setDependencies($files->css);

			if($cache->get() === false) {
				foreach($files->css as $file) {
					$fcache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/yui/css/.src/' . sha1($file) . '.css')
						->setMode('raw')
						->setDependencies($file);

					if(($data = $fcache->get()) === false) {
						$data         = file_get_contents($file);
						$patterns     = array();
						$replacements = array();

						if(preg_match_all('/url\((?:"|\'){0,1}(.+?)(?:"|\'){0,1}\)/i', $data, $matches) > 0) {
							foreach($matches[1] as $match) {
								$patterns[]     = 'url(' . $match . ')';
								$replacement    = Glue\Helper\Modifier::cleanPath(dirname($file) . '/' . $match);
								$replacement    = preg_replace('/^' . preg_quote($path['global'], '/') . '/', '', $replacement);
								$replacements[] = ($url['relative'] !== '/') ? 'url(' . $url['relative'] . $replacement . ')' : 'url(' . $replacement . ')';
							}

							$data = str_replace($patterns, $replacements, $data);
						}

						$data = \Glue\Helper\Yui::compress('css', $data);

						$fcache->setData($data)->set();
					}

					$source->css[] = $data;

					unset($fcache);
					unset($data);
				}

				$cache->setData(implode(chr(10), $source->css))->set();
			}

			$return[] = '<link rel="stylesheet" media="all" href="' . \Glue\Helper\Url::make($cache->cid) . '" />';

			unset($cache);
		}

		if(preg_match_all('/<script.+src="(.+?)".+>/i', $buffer, $results) != false) {
			foreach($results[0] as $index => $tag) {
				if(preg_match('/type="text\/javascript"/i', $tag) != false) {
					$files->js[] = $path['global'] . '/' . $results[1][$index];
				}
			}
		}

		if(count($files->js) > 0) {
			$cache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/yui/js/' . sha1(implode(';', $files->js)) . '.js')
				->setMode('raw')
				->setDependencies($files->js);

			if($cache->get() === false) {
				foreach($files->js as $file) {
					$fcache = \Glue\Objects\Cache\File::getInstance($path['local'] . '/cache/yui/js/.src/' . sha1($file) . '.js')
						->setMode('raw')
						->setDependencies($file);

					if(($data = $fcache->get()) === false) {
						$data = \Glue\Helper\Yui::compress('js', file_get_contents($file));

						$fcache->setData($data)->set();
					}

					$source->js[] = $data;

					unset($fcache);
					unset($data);
				}

				$cache->setData(implode(chr(10), $source->js))->set();
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