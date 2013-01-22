<?php
/*
 * Dwoo plugin
 * -------------------------------------------------------------
 * File:     image.php
 * Purpose:  modifies an image according to parameters and 
 *           handles caching
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.qoopido.de
 *
 * Changelog:
 * 2011-03-24 Initial release
 * -------------------------------------------------------------
 */
class Dwoo_Plugin_image extends Dwoo_Block_Plugin {
	private $return; // raw | path[:absolute|relative] | url[:absolute|relative] | tag[:absolute|relative]
	private $lazy;
	private $alt;
	private $title;
	private $directory;
	private $attributes;
	private $calls;
	private $interlace;
	private $quality;
	private $filter;

	private static $methods = NULL;
	private static $path    = NULL;
	private static $url     = NULL;

	public function init($return = 'path:absolute', $lazy = NULL, $alt = NULL, $title = NULL, $directory = NULL, $interlace = NULL, $quality = NULL, $filter = NULL, array $rest = array()) {
		if(self::$methods === NULL) {
			self::$methods = array_flip(get_class_methods('\Glue\Entity\Image'));
		}

		if(self::$path === NULL) {
			self::$path = \Glue\Component\Environment::getInstance()->get('path');
		}

		if(self::$url === NULL) {
			self::$url = \Glue\Component\Environment::getInstance()->get('url');
		}

		$this->return     = (!isset($return)) ? 'path:absolute' : preg_replace('/^(path|url|tag)$/', '\1:relative', strtolower($return));
		$this->lazy       = (isset($lazy) && $lazy === 'true') ? true : false;
		$this->alt        = (!isset($alt)) ? NULL : $alt;
		$this->title      = (!isset($title)) ? NULL : $title;
		$this->directory  = (!isset($directory)) ? NULL : \Glue\Helper\Modifier::cleanPath(preg_replace('/[^a-z0-9._\-\/]/i', '', $directory)); // \Glue\Helper\Path::clean(preg_replace('/[^a-z0-9._\-\/]/i', '', $directory));
		$this->interlace  = (!isset($interlace)) ? false : true;
		$this->quality    = (!isset($quality)) ? NULL : (int) $quality;
		$this->filter     = (!isset($filter)) ? NULL : (int) $filter;
		$this->attributes = false;
		$this->calls      = array();

		// parse parameters
			foreach($rest as $name => $value) {
				$_name = preg_replace('/\d*$/i', '', $name);

				if(isset(self::$methods[$_name])) {
					$reflection = new ReflectionMethod('\Glue\Entity\Image', $_name);

					if($reflection->isPublic() === true && $reflection->isConstructor() === false && $reflection->isDestructor() === false) {
						$index = count($this->calls);

						$this->calls[$index]             = new stdClass();
						$this->calls[$index]->method     = $_name;

						if($value !== true) {
							$this->calls[$index]->parameters = explode(',', $value);

							foreach($this->calls[$index]->parameters as $k => $v) {
								switch($v) {
									case 'false':
										$this->calls[$index]->parameters[$k] = false;
										break;
									case 'true':
										$this->calls[$index]->parameters[$k] = true;
										break;
									case 'NULL':
										$this->calls[$index]->parameters[$k] = NULL;
										break;
									case 'null':
										$this->calls[$index]->parameters[$k] = false;
										break;
								}
							}
						}
					}

					unset($reflection);
				} else {
					$this->attributes .= $name . '="' . addcslashes($value, '"') . '" ';
				}

				unset($rest[$name]);
			}
	}

	public function process() {
		$return = false;

		$id     = (empty($_directory)) ? self::$path['local'] . '/cache/assets/img/' . sha1(serialize(array($this->buffer, $this->interlace, $this->quality, $this->filter, $this->calls))) . '.png' : self::$path['local'] . '/cache/assets/img/' . $_directory . '/' . sha1(serialize(array($this->buffer, $this->interlace, $this->quality, $this->filter, $this->calls))) . '.png';
		$cache  = \Glue\Entity\Cache\File::getInstance($id)->setMode('raw');

		if(\Glue\Helper\Validator::isLocal($this->buffer)) {
			$cache->setDependencies(self::$path['global'] . '/' . $this->buffer);
		}

		if(($data = $cache->get()) === false) {
			$image = new \Glue\Entity\Image($this->buffer);

			foreach($this->calls as $call) {
				if(isset($call->parameters)) {
					call_user_func_array(array($image, $call->method), $call->parameters);
				} else {
					call_user_func(array($image, $call->method));
				}
			}

			$data = $image->get('png', $this->interlace, $this->quality, $this->filter);

			$cache->setData($data)->set();

			unset($image);
		}

		switch($this->return) {
			case 'raw':
				$return = $data;
				break;
			default:
				$this->return = explode(':', $this->return);

				switch($this->return[0]) {
					case 'path':
						$return = ($this->return[1] == 'relative') ? preg_replace('/^' . preg_quote(self::$path['global'], '/') . '/', '.', $cache->path) : $cache->cid;
						break;
					case 'url':
						$return = ($this->return[1] == 'relative') ? \Glue\Helper\Url::make($cache->cid) : self::$url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid);
						break;
					case 'tag':
						$size   = getimagesize($cache->cid);

						if($this->lazy === true) {
							$return = ($this->return[1] == 'relative') ? '<img data-lazyimage="' . \Glue\Helper\Url::make($cache->cid) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $this->alt . '" title="' . $this->title . '" ' . $this->attributes . ' />' : '<img src="' . (self::$url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid)) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $this->alt . '" title="' . $this->title . '" ' . $this->attributes . ' />';
						} else {
							$return = ($this->return[1] == 'relative') ? '<img src="' . \Glue\Helper\Url::make($cache->cid) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $this->alt . '" title="' . $this->title . '" ' . $this->attributes . ' />' : '<img src="' . (self::$url['relative'] . '/' . \Glue\Helper\Url::make($cache->cid)) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="' . $this->alt . '" title="' . $this->title . '" ' . $this->attributes . ' />';
						}

						$return = preg_replace('/\w+=""/i', '', $return);

						unset($size);
						break;
				}

				break;
		}

		// clean up
			unset($id);

		return $return;
	}
}
?>