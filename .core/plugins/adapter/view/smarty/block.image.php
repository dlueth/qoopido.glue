<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.image.php
 * Purpose:  creates thumbnails
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-12-15 Initial release
 * -------------------------------------------------------------
 */
function smarty_block_image($parameters, $buffer, Smarty_Internal_Template $template, &$repeat) {
	if(!$repeat && isset($buffer)){
		static $methods = NULL;
		static $path    = NULL;
		static $url     = NULL;

		if($methods === NULL) {
			$methods = array_flip(get_class_methods('\Glue\Entity\Image'));
		}

		if($path === NULL) {
			$path = \Glue\Component\Environment::getInstance()->get('path');
		}

		if($url === NULL) {
			$url = \Glue\Component\Environment::getInstance()->get('url');
		}

		$return      = false;

		$_return     = (!isset($parameters['return'])) ? 'path:absolute' : preg_replace('/^(path|url|tag)$/', '\1:relative', strtolower($parameters['return']));
		$_lazy       = (isset($parameters['lazy']) && $parameters['lazy'] === 'true') ? true : false;
		$_alt        = (!isset($parameters['alt'])) ? NULL : $parameters['alt'];
		$_title      = (!isset($parameters['title'])) ? NULL : $parameters['title'];
		$_directory  = (!isset($parameters['directory'])) ? NULL : \Glue\Helper\Modifier::cleanPath(preg_replace('/[^a-z0-9._\-\/]/i', '', $parameters['directory']));
		$_interlace  = (!isset($parameters['interlace'])) ? false : true;
		$_quality    = (!isset($parameters['quality'])) ? NULL : (int) $parameters['quality'];
		$_filter     = (!isset($parameters['filter'])) ? NULL : (int) $parameters['filter'];
		$_attributes = false;
		$_calls      = array();

		unset($parameters['return']);
		unset($parameters['lazy']);
		unset($parameters['alt']);
		unset($parameters['title']);
		unset($parameters['directory']);
		unset($parameters['interlace']);
		unset($parameters['quality']);
		unset($parameters['filter']);

		// parse parameters
			foreach($parameters as $name => $value) {
				$_name = preg_replace('/\d*$/i', '', $name);

				if(isset($methods[$_name])) {
					$reflection = new ReflectionMethod('\Glue\Entity\Image', $_name);

					if($reflection->isPublic() === true && $reflection->isConstructor() === false && $reflection->isDestructor() === false) {
						$index = count($_calls);

						$_calls[$index]         = new stdClass();
						$_calls[$index]->method = $_name;

						if($value !== true) {
							$_calls[$index]->parameters = explode(',', $value);

							foreach($_calls[$index]->parameters as $k => $v) {
								switch($v) {
									case 'false':
										$_calls[$index]->parameters[$k] = false;
										break;
									case 'true':
										$_calls[$index]->parameters[$k] = true;
										break;
									case 'NULL':
										$_calls[$index]->parameters[$k] = NULL;
										break;
									case 'null':
										$_calls[$index]->parameters[$k] = NULL;
										break;
								}
							}
						}
					}

					unset($reflection);
				} else {
					$_attributes .= $name . '="' . addcslashes($value, '"') . '" ';
				}

				unset($parameters[$name]);
			}

			unset($parameters);

		// process
			$id    = (empty($_directory)) ? $path['local'] . '/cache/image/' . sha1(serialize(array($buffer, $_interlace, $_quality, $_filter, $_calls))) . '.png' : $path['local'] . '/cache/img/' . $_directory . '/' . sha1(serialize(array($buffer, $_interlace, $_quality, $_filter, $_calls))) . '.png';
			$cache = \Glue\Entity\Cache\File::getInstance($id)->setMode('raw');

			if(\Glue\Helper\Validator::isLocal($buffer)) {
				$cache->setDependencies($path['global'] . '/' . $buffer);
			}

			if(($data = $cache->get()) === false) {
				$image = new \Glue\Entity\Image($buffer);

				foreach($_calls as $call) {
					if(isset($call->parameters)) {
						call_user_func_array(array($image, $call->method), $call->parameters);
					} else {
						call_user_func(array($image, $call->method));
					}
				}

				$data = $image->get('png', $_interlace, $_quality, $_filter);

				$cache->setData($data)->set();

				unset($image);
			}

			switch($_return) {
				case 'raw':
					$return = $data;
					break;
				default:
					$_return = explode(':', $_return);

					switch($_return[0]) {
						case 'path':
							$return = ($_return[1] == 'relative') ? preg_replace('/^' . preg_quote($path['global'], '/') . '/', '.', $cache->path) : $cache->cid;
							break;
						case 'url':
							$return = ($_return[1] == 'relative') ? \Glue\Helper\Url::make($cache->cid) : $url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid);
							break;
						case 'tag':
							$size   = getimagesize($cache->cid);

							if($_lazy === true) {
								$return = ($_return[1] == 'relative') ? '<img data-lazyimage="' . \Glue\Helper\Url::make($cache->cid) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $_alt . '" title="' . $_title . '" ' . $_attributes . ' />' : '<img src="' . ($url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid)) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $_alt . '" title="' . $_title . '" ' . $_attributes . ' />';
							} else {
								$return = ($_return[1] == 'relative') ? '<img src="' . \Glue\Helper\Url::make($cache->cid) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $_alt . '" title="' . $_title . '" ' . $_attributes . ' />' : '<img src="' . ($url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid)) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $_alt . '" title="' . $_title . '" ' . $_attributes . ' />';
							}

							$return = preg_replace('/\w+=""/i', '', $return);

							unset($size);
							break;
					}

					break;
			}

		unset($_return);
		unset($_alt);
		unset($_title);
		unset($_directory);
		unset($_interlace);
		unset($_quality);
		unset($_filter);
		unset($_attributes);
		unset($_calls);
		unset($id);
		unset($cache);
		unset($data);

		return $return;
	}
}
?>