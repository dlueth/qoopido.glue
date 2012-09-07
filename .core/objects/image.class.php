<?php
namespace Glue\Objects;

/**
 * Object for image manipulation
 *
 * @require PHP "GD" extension
 * @require PHP "imageconvolution" function for sharpen() and blur()
 * @require PHP "CURL" extension or "allow_url_fopen = On" for load([remote file])
 * @require PHP "Exif" extension to read correct orientation from JPEG
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Image extends \Glue\Abstracts\Base\Chainable {
	/**
	 * Property to store image resource
	 */
	protected $image;

	/**
	 * Property to store width
	 */
	protected $width;

	/**
	 * Property to store height
	 */
	protected $height;

	/**
	 * Property to store temporary values
	 */
	protected $temporary = NULL;

	/**
	 * Property to store presence of imageconvolutaion
	 */
	protected static $imageconvolution = NULL;

	/**
	 * Property to store presence of imagefilters
	 */
	protected static $imagefilter = NULL;

	/**
	 * Property to store presence of imagelayereffects
	 */
	protected static $imagelayereffect = NULL;

	/**
	 * Property to store presence of CURL
	 */
	protected static $curl = NULL;

	/**
	 * Property to store status of allow_url_fopen
	 */
	protected static $urlfopen = NULL;

	/**
	 * Property to store presence of Exif
	 */
	protected static $exif = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	public static function __once() {
		if(extension_loaded('gd') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'GD'), EXCEPTION_EXTENSION_MISSING));
		}

		try {
			self::$imageconvolution = function_exists('imageconvolution');
			self::$imagefilter      = function_exists('imagefilter');
			self::$imagelayereffect = function_exists('imagelayereffect');
			self::$curl             = extension_loaded('curl');
			self::$urlfopen         = (bool) ini_get('allow_url_fopen');
			self::$exif             = extension_loaded('exif');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class constructor
	 *
	 * @param mixed $image [optional]
	 *
	 * @throw \RuntimeException
	 */
	public function __initialize($image = NULL) {
		try {
			$this->temporary = array('images' => array(), 'colors' => array());

			if($image !== NULL && !empty($image)) {
				$this->load($image);
			}

			unset($image);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Class destructor
	 */
	public function __destruct() {
		if(is_resource($this->image) && get_resource_type($this->image) === 'gd') {
			imagedestroy($this->image);
		}

		unset($this->width);
		unset($this->height);
		unset($this->image);
	}

	/**
	 * Magic method to retrieve restricted properties
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property) {
		switch($property) {
			case 'width':
				return $this->width;
				break;
			case 'height':
				return $this->height;
				break;
		}
	}

	/**
	 * Method to load an image from whatever is passed in
	 *
	 * @param mixed $image
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	public function load($image) {
		try {
			// try to initialize from source image
				$orientation = 0;

				if(is_resource($image) && get_resource_type($image) === 'gd') {
					$this->_set($image, imagesx($image), imagesy($image));
				} else {

					// source image is NOT a GD resource
						if(is_file($image) && is_readable($image)) {
							// source image is a path to an existing and readable file
								$size = getimagesize($image);

								if($size != false) {
									switch($size[2]) {
										case 1: // GIF
											$image = imagecreatefromgif($image);
											break;
										case 2: // JPEG
											if(self::$exif === true) {
												$exif  = exif_read_data($image);
												$image = imagecreatefromjpeg($image);

												if(isset($exif['Orientation']) && $exif['Orientation'] > 1) {
													$orientation = $exif['Orientation'];
												}

												unset($exif);
											} else {
												$image = imagecreatefromjpeg($image);
											}

											break;
										case 3: // PNG
											$image = imagecreatefrompng($image);
											break;
									}

									$this->_set($image, $size[0], $size[1]);
								}

								unset($size);
						} else {
							// source image could still be a string or a remote url
								if(\Glue\Helper\Validator::isLocal($image) === false) {
									$temp = false;

									if(self::$curl === true) {
										$curl    = \Glue\Modules\Curl::getInstance();
										$request = curl_init($image);

										curl_setopt_array($request, array(
											CURLOPT_RETURNTRANSFER => true,
											CURLOPT_HEADER         => false,
											CURLOPT_MAXREDIRS      => 10
										));

										$request = $curl->add($request);
										$temp = $request->response;

										unset($curl, $request);
									} elseif(self::$urlfopen === true) {
										$temp = @file_get_contents($image);
									}

									if(!empty($temp)) {
										$image = $temp;
									}

									unset($temp);
								}

								$image = imagecreatefromstring($image);

								if($image != false) {
									$this->_set($image, imagesx($image), imagesy($image));
								}
						}
				}

			unset($image);

			if($orientation > 1) {
				switch($orientation) {
					case 2: // horizontal flip
						$this->flip(1);
						break;
					case 3: // 180 rotate left
						$this->rotate(-180);
						break;
					case 4: // vertical flip
						$this->flip(2);
						break;
					case 5: // vertical flip + 90 rotate right
						$this->flip(2);
						$this->rotate(90);
						break;
					case 6: // 90 rotate right
						$this->rotate(90);
						break;
					case 7: // horizontal flip + 90 rotate right
						$this->flip(1);
						$this->rotate(90);
						break;
					case 8: // 90 rotate left
						$this->rotate(-90);
						break;
				}
			}

			unset($orientation);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert the image to grayscale
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	public function grayscale() {
		try {
			if(self::$imagefilter === true) {
				imagefilter($this->image, IMG_FILTER_GRAYSCALE);
			} else {
				for($x = 0; $x < $this->width; $x++) {
					for($y = 0; $y < $this->height; $y++) {
						$this->temporary['colors']['source']    = imagecolorat($this->image, $x, $y);
						$this->temporary['colors']['intensity'] = \Glue\Helper\Converter::color2grayscale($this->temporary['colors']['source']);
						$this->temporary['colors']['final']     = \Glue\Helper\Converter::rgb2color($this->temporary['colors']['intensity']['r'], $this->temporary['colors']['intensity']['g'], $this->temporary['colors']['intensity']['b'], $this->temporary['colors']['intensity']['a']);

						imagesetpixel($this->image, $x, $y, $this->temporary['colors']['final']);
					}

					unset($y);
				}

				$this->_clean();

				unset($x);
			}

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to invert the image
	 *
	 * @return object
	 *
	 * @throw \RuntimeException
	 */
	public function invert() {
		try {
			if(self::$imagefilter === true) {
				imagefilter($this->image, IMG_FILTER_NEGATE);
			} else {
				for($x = 0; $x < $this->width; $x++) {
					for($y = 0; $y < $this->height; $y++) {
						$this->temporary['colors']['source'] = imagecolorat($this->image, $x, $y);
						$this->temporary['colors']['final']  = \Glue\Helper\Converter::color2rgb($this->temporary['colors']['source']);
						$this->temporary['colors']['final']  = \Glue\Helper\Converter::rgb2color(255 - $this->temporary['colors']['final']['r'], 255 - $this->temporary['colors']['final']['g'], 255 - $this->temporary['colors']['final']['b'], $this->temporary['colors']['final']['a']);

						imagesetpixel($this->image, $x, $y, $this->temporary['colors']['final']);
					}

					unset($y);
				}

				$this->_clean();

				unset($x);
			}

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to adjust brightness of the image
	 *
	 * @param int $percent [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function brightness($percent = 25) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$percent' => array($percent, 'isNumeric', array('isBetween', array(0, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			if(self::$imagefilter === true) {
				imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $percent);
			} else {
				$factor = ((int) $percent) / 100;

				for($x = 0; $x < $this->width; $x++) {
					for($y = 0; $y < $this->height; $y++) {
						$this->temporary['colors']['source']     = imagecolorat($this->image, $x, $y);
						$this->temporary['colors']['source']     = \Glue\Helper\Converter::color2rgb($this->temporary['colors']['source']);
						$this->temporary['colors']['final']      = $this->temporary['colors']['source'];
						$this->temporary['colors']['final']['r'] = min(255, max(0, $this->temporary['colors']['final']['r'] + ($this->temporary['colors']['final']['r'] * $factor)));
						$this->temporary['colors']['final']['g'] = min(255, max(0, $this->temporary['colors']['final']['g'] + ($this->temporary['colors']['final']['g'] * $factor)));
						$this->temporary['colors']['final']['b'] = min(255, max(0, $this->temporary['colors']['final']['b'] + ($this->temporary['colors']['final']['b'] * $factor)));
						$this->temporary['colors']['final']      = \Glue\Helper\Converter::rgb2color($this->temporary['colors']['final']['r'], $this->temporary['colors']['final']['g'], $this->temporary['colors']['final']['b'], $this->temporary['colors']['final']['a']);

						imagesetpixel($this->image, $x, $y, $this->temporary['colors']['final']);
					}

					unset($y);
				}

				$this->_clean();

				unset($factor, $x);
			}

			unset($percent, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to adjust hue, saturation and lightness of the image
	 *
	 * @param int $hue [optional]
	 * @param int $saturation [optional]
	 * @param int $lightness [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function hsl($hue = 0, $saturation = NULL, $lightness = NULL) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$hue'        => array($hue, 'isNumeric', array('isBetween', array(-180, 180))),
			'$saturation' => array($saturation, 'isNumeric', array('isBetween', array(-100, 100))),
			'$lightness'  => array($lightness, 'isNumeric', array('isBetween', array(-100, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$hue        = min(180, max(-180, (int) $hue));
			$hue        = ($hue < 0) ? 360 + $hue : $hue;
			$saturation = ($saturation !== NULL) ? min(100, max(-100, (int) $saturation)) / 100 : NULL;
			$lightness  = ($lightness !== NULL) ? min(100, max(-100, (int) $lightness)) / 100 : NULL;

			for($x = 0; $x < $this->width; $x++) {
				for($y = 0; $y < $this->height; $y++) {
					$this->temporary['colors']['source'] = imagecolorat($this->image, $x, $y);
					$this->temporary['colors']['source'] = \Glue\Helper\Converter::color2rgb($this->temporary['colors']['source']);
					$this->temporary['colors']['final']  = \Glue\Helper\Converter::rgb2hsl($this->temporary['colors']['source']['r'], $this->temporary['colors']['source']['g'], $this->temporary['colors']['source']['b'], $this->temporary['colors']['source']['a']);

					// process parameters
						// hue
							$this->temporary['colors']['final']['h'] += $hue;

						// saturation
							if($saturation !== NULL) {
								$this->temporary['colors']['final']['s'] = $saturation;
							}

						// lightness
							if($lightness !== NULL) {
								$this->temporary['colors']['final']['l']  = min(1, max(0, $this->temporary['colors']['final']['l'] + $lightness));
							}

					$this->temporary['colors']['final'] = \Glue\Helper\Converter::hsl2rgb($this->temporary['colors']['final']['h'], $this->temporary['colors']['final']['s'], $this->temporary['colors']['final']['l'], $this->temporary['colors']['final']['a']);
					$this->temporary['colors']['final'] = \Glue\Helper\Converter::rgb2color($this->temporary['colors']['final']['r'], $this->temporary['colors']['final']['g'], $this->temporary['colors']['final']['b'], $this->temporary['colors']['final']['a']);

					imagesetpixel($this->image, $x, $y, $this->temporary['colors']['final']);
				}

				unset($y);
			}

			$this->_clean();

			unset($hue, $saturation, $lightness, $result, $x);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to resize the image
	 *
	 * mode = 0: scale to width & height (ignoring aspect ratio)
	 * mode = 1: scale to width (keeping aspect ratio)
	 * mode = 2: scale to height (keeping aspect ratio)
	 * mode = 3: scale to shortest side (keeping aspect ratio)
	 * mode = 4: scale to longest side (keeping aspect ratio)
	 *
	 * @param mixed $size
	 * @param int $mode [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function resize($size, $mode = 3) {
		$size = (is_scalar($size)) ? array_fill(0, 2, $size) : $size;

		if(($result = \Glue\Helper\validator::batch(array(
			'@$size'   => array($size, 'isNumeric', array('isGreater', array(0))),
			'$mode'    => array($mode, 'isNumeric', array('isBetween', array(0, 4)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			switch($mode) {
				case 1:
					$size[1] = (int) round($this->height * ($size[0] / $this->width));
					break;
				case 2:
					$size[0] = (int) round($this->width * ($size[1] / $this->height));
					break;
				case 3:
					$ratio = array($size[0] / $this->width, $size[1] / $this->height);

					if($ratio[0] > $ratio[1]) {
						// portrait
						$size[1] = (int) round($this->height * $ratio[0]);
					} else {
						// landscape
						$size[0] = (int) round($this->width * $ratio[1]);
					}

					unset($ratio);

					break;
				case 4:
					$ratio = array($size[0] / $this->width, $size[1] / $this->height);

					if($ratio[0] > $ratio[1]) {
						// portrait
						$size[0] = (int) round($this->width * $ratio[1]);
					} else {
						// landscape
						$size[1] = (int) round($this->height * $ratio[0]);
					}

					unset($ratio);

					break;
			}

			// process
				if($size[0] != $this->width || $size[1] != $this->height) {
					$this->temporary['images']['final'] = imagecreatetruecolor($size[0], $size[1]);

					imagealphablending($this->temporary['images']['final'], false);
					imagesavealpha($this->temporary['images']['final'], true);
					imagecopyresampled($this->temporary['images']['final'], $this->image, 0, 0, 0, 0, $size[0], $size[1], $this->width, $this->height);

					$this->_set($this->temporary['images']['final'], $size[0], $size[1]);
				}

			unset($size, $mode, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to crop the image
	 *
	 * @param int $width
	 * @param int $height
	 * @param bool $x [optional]
	 * @param bool $y [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function crop($width, $height, $x = false, $y = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$width'   => array($width, 'isNumeric', array('isGreater', array(0))),
			'$height'  => array($height, 'isNumeric', array('isGreater', array(0)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// process
				if($x === false) {
					$x = floor($this->width / 2 - $width / 2);
				}

				if($y === false) {
					$y = floor($this->height / 2 - $height / 2);
				}

				$this->temporary['images']['final'] = imagecreatetruecolor($width, $height);

				imagealphablending($this->temporary['images']['final'], false);
				imagesavealpha($this->temporary['images']['final'], true);
				imagecopy($this->temporary['images']['final'], $this->image, 0, 0, $x, $y, $width, $height);

				$this->_set($this->temporary['images']['final'], $width, $height);

			unset($width, $height, $x, $y, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to rotate the image clockwise
	 *
	 * @param int $angle
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function rotate($angle) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$angle'   => array($angle, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// process
				$angle = 360 - $angle;

				$this->temporary['images']['final'] = imagerotate($this->image, $angle, -1);

				imagealphablending($this->temporary['images']['final'], false);
				imagesavealpha($this->temporary['images']['final'], true);

				$this->_set($this->temporary['images']['final'], imagesx($this->temporary['images']['final']), imagesy($this->temporary['images']['final']));

				unset($degrees, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to flip the image around x- or y-axes
	 *
	 * axes = 1: flip around x-axes
	 * axes = 2: flip around y-axes
	 *
	 * @param int $axes
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function flip($axes = 1) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$axes'   => array($axes, 'isNumeric', array('isBetween', array(1, 2)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// process
				$this->temporary['images']['final'] = imagecreatetruecolor($this->width, $this->height);

				imagealphablending($this->temporary['images']['final'], false);
				imagesavealpha($this->temporary['images']['final'], true);

				switch($axes) {
					case 1:
						for($x = 0; $x < $this->width; $x++) {
							for($y = 0; $y < $this->height; $y++) {
								imagecopy($this->temporary['images']['final'], $this->image, $x, $this->height - $y - 1, $x, $y, 1, 1);
							}

							unset($y);
						}

						unset($x);

						break;
					case 2:
						for($x = 0; $x < $this->width; $x++) {
							for($y = 0; $y < $this->height; $y++) {
								imagecopy($this->temporary['images']['final'], $this->image, $this->width - $x - 1, $y, $x, $y, 1, 1);
							}

							unset($y);
						}

						unset($x);

						break;
				}

				$this->_set($this->temporary['images']['final']);

			unset($axes, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to blur the image
	 *
	 * @param int $amount [optional]
	 * @param bool $alpha [optional]
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function blur($amount = 50) {
		if(self::$imageconvolution !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'function' => 'imageconvolution'), EXCEPTION_FUNCTION_MISSING));
		}

		if(($result = \Glue\Helper\validator::batch(array(
			'$amount'   => array($amount, 'isNumeric', array('isBetween', array(1, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$amount  = 100 - $amount;

			$this->temporary['images']['final'] = imagecreatetruecolor($this->width, $this->height);

			imagealphablending($this->temporary['images']['final'], false);
			imagesavealpha($this->temporary['images']['final'], true);
			imagecopymerge($this->temporary['images']['final'], $this->image, 0, 0, 0, 0, $this->width, $this->height, 100);

			$matrix = array(
				array(1, 2, 1),
				array(2, $amount, 2),
				array(1, 2, 1)
			);

			imageconvolution($this->temporary['images']['final'], $matrix, $amount + 12, 0);

			$this->_set($this->temporary['images']['final']);

			unset($amount, $alpha, $result, $matrix);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to sharpen the image
	 *
	 * @param int $amount [optional]
	 * @param int $radius [optional]
	 * @param int $threshold [optional]
	 *
	 * @return object
	 *
	 * @throw \LogicException
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function sharpen($amount = 50) {
		if(self::$imageconvolution !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'function' => 'imageconvolution'), EXCEPTION_FUNCTION_MISSING));
		}

		if(($result = \Glue\Helper\validator::batch(array(
			'$amount'    => array($amount, 'isNumeric', array('isBetween', array(1, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$amount = 100 - ($amount / (100 / 80));

			$this->temporary['images']['final'] = imagecreatetruecolor($this->width, $this->height);

			imagealphablending($this->temporary['images']['final'], false);
			imagesavealpha($this->temporary['images']['final'], true);
			imagecopy($this->temporary['images']['final'], $this->image, 0, 0, 0, 0, $this->width, $this->height);

			$matrix = array(
				array(-1, -2, -1),
				array(-2, $amount, -2),
				array(-1, -2, -1)
			);

			imageconvolution($this->temporary['images']['final'], $matrix, $amount - 12, 0);

			$this->_set($this->temporary['images']['final']);

			unset($amount, $result, $matrix);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to add a reflection to the image
	 *
	 * @param int $aperture [optional]
	 * @param int $height [optional]
	 * @param int $alpha [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function addReflection($aperture = 80, $height = INF, $alpha = 40) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$aperture' => array($aperture, 'isNumeric', array('isBetween', array(0, 100))),
			'$height'   => array($height, 'isNumeric', array('isGreater', array(0))),
			'$alpha'    => array($alpha, 'isNumeric', array('isBetween', array(0, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// calibrate parameters
				$aperture = min(100, $aperture);
				$aperture = round($this->height * ($aperture / 100));
				$height   = ($height === INF) ? round($this->height * ($aperture / 100)) : (int) $height;
				$alpha    = ((100 - min(100, $alpha)) / 100) * 127;

			// process
				$this->temporary['images']['final'] = imagecreatetruecolor($this->width, $this->height + $height);

				imagealphablending($this->temporary['images']['final'], false);
				imagesavealpha($this->temporary['images']['final'], true);
				imagecopy($this->temporary['images']['final'], $this->image, 0, 0, 0, 0, $this->width, $this->height);

				$this->temporary['images']['reflection'] = new Image($this->image);
				$this->temporary['images']['reflection']->crop($this->width, $aperture, 0, $this->height - $aperture);
				$this->temporary['images']['reflection']->resize($this->width, $height);
				$this->temporary['images']['reflection']->flip();

				$this->temporary['images']['copy'] =& $this->temporary['images']['reflection']->getResource();

				if(self::$imagelayereffect === true) {
					imagelayereffect($this->temporary['images']['copy'], IMG_EFFECT_OVERLAY);

					for($y = 0; $y < $height; $y++) {
						$this->temporary['colors']['alpha'] = \Glue\Helper\Converter::rgb2color(127, 127, 127, round(((127 - $alpha) / $height) * $y + $alpha));

						imagefilledrectangle($this->temporary['images']['copy'], 0, $y, $this->width, $y, $this->temporary['colors']['alpha']);
					}

					imagecopy($this->temporary['images']['final'], $this->temporary['images']['copy'], 0, $this->height, 0, 0, $this->width, $height);
				} else {
					for($y = 0; $y < $height; $y++) {
						$this->temporary['colors']['alpha'] = round(((127 - $alpha) / $height) * $y + $alpha);

						for($x = 0; $x < $this->width; $x++) {
							$this->temporary['colors']['source'] = imagecolorat($this->temporary['images']['copy'], $x, $y);
							$this->temporary['colors']['source'] = \Glue\Helper\Converter::color2rgb($this->temporary['colors']['source']);
							$this->temporary['colors']['source'] = \Glue\Helper\Converter::rgb2color($this->temporary['colors']['source']['r'], $this->temporary['colors']['source']['g'], $this->temporary['colors']['source']['b'], min(127, $this->temporary['colors']['alpha'] + $this->temporary['colors']['source']['a']));

							imagesetpixel($this->temporary['images']['final'], $x, $this->height + $y, $this->temporary['colors']['source']);
						}

						unset($x);
					}
				}

				$this->_set($this->temporary['images']['final'], NULL, $this->height + $height);

			unset($aperture, $height, $alpha, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to add a border to the image
	 *
	 * @param string $color [optional]
	 * @param int $stroke [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function addBorder($color = '#000', $stroke = 1) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$color'  => array($color, 'isString', 'isNotEmpty'),
			'$stroke' => array($stroke, 'isNumeric', array('isGreater', array(0)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			// process
				$color  = \Glue\Helper\Converter::hex2rgb($color);
				$color  = \Glue\Helper\Converter::rgb2color($color['r'], $color['g'], $color['b'], $color['a']);
				$width  = $this->width + 2 * $stroke;
				$height = $this->height + 2 * $stroke;

				$this->temporary['images']['final'] = imagecreatetruecolor($width, $height);

				imagefill($this->temporary['images']['final'], 0, 0, $color);
				imagecopy($this->temporary['images']['final'], $this->image, $stroke, $stroke, 0, 0, $this->width, $this->height);

				$this->_set($this->temporary['images']['final'], $width, $height);

			unset($color, $stroke, $result, $width, $height);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to add a dropshadow to the image
	 *
	 * @param string $color [optional]
	 * @param string $background [optional]
	 * @param int $alpha [optional]
	 * @param int $angle [optional]
	 * @param int $distance [optional]
	 * @param int $size [optional]
	 * @param int $spread [optional]
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function addShadow($color = '#000', $background = '#fff', $alpha = 50, $angle = 135, $distance = 2, $size = 5, $spread = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$color'      => array($color, 'isString', 'isNotEmpty'),
			'$background' => array($background, 'isString', 'isNotEmpty'),
			'$alpha'      => array($alpha, 'isNumeric', array('isBetween', array(0, 100))),
			'$angle'      => array($angle, 'isNumeric', array('isBetween', array(0, 359))),
			'$distance'   => array($distance, 'isNumeric', array('isGreater', array(0, true))),
			'$size'       => array($size, 'isNumeric', array('isGreater', array(0))),
			'$spread'     => array($spread, 'isNumeric', array('isGreater', array(0, true)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$color      = \Glue\Helper\Converter::hex2rgb($color);
			$background = \Glue\Helper\Converter::hex2rgb($background);

			// calibrate parameters
			$alpha  = ((100 - min(100, $alpha)) / 100) * 127;
			$angle  = deg2rad(max(0, min(359, $angle)));
			$spread = min($size - 2, $spread);

			$offset = array(
				'x' => round(sin($angle) * $distance),
				'y' => round(cos($angle) * $distance * -1)
			);

			$offset['shadow'] = array(
				'x' => max(0, $offset->x),
				'y' => max(0, $offset->y)
			);

			$offset['final'] = array(
				'x' => abs(min(0, $offset->x)) + $size,
				'y' => abs(min(0, $offset->y)) + $size
			);

			// process
			$width  = $this->width + $size * 2;
			$height = $this->height + $size * 2;

			$this->temporary['images']['final'] = imagecreatetruecolor($width + abs($offset['x']), $height + abs($offset['y']));

			imagealphablending($this->temporary['images']['final'], false);
			imagesavealpha($this->temporary['images']['final'], true);

			$this->temporary['images']['shadow'] = imagecreatetruecolor($width, $height);

			$this->temporary['colors']['background'] = ($background['r'] << 16) + ($background['g'] << 8) + $background['b'] + (127 << 24);

			imagefill($this->temporary['images']['final'], 0, 0, $this->temporary['colors']['background']);

			$this->temporary['colors']['background'] = \Glue\Helper\Converter::rgb2color($background['r'], $background['g'], $background['b'], $background['a']);
			$this->temporary['colors']['shadow']     = \Glue\Helper\Converter::rgb2color($color['r'], $color['g'], $color['b'], $alpha);

			imagefill($this->temporary['images']['shadow'], 0, 0, $this->temporary['colors']['background']);
			imagefilledrectangle($this->temporary['images']['shadow'], $size - $spread, $size - $spread, $this->width + $size + $spread - 1, $this->height + $size + $spread - 1, $this->temporary['colors']['shadow']);

			$this->temporary['images']['shadow'] = new Image($this->temporary['images']['shadow']);

			for($i = 0; $i < $size - $spread - 1; $i++) {
				$this->temporary['images']['shadow']->blur(100);
			}

			$this->temporary['images']['shadow']->blur(100);

			$this->temporary['images']['shadow'] = $this->temporary['images']['shadow']->getResource();

			$this->temporary['colors']['delta'] = array(
				'r' => abs($color['r'] - $background['r']),
				'g' => abs($color['g'] - $background['g']),
				'b' => abs($color['b'] - $background['b'])
			);

			arsort($this->temporary['colors']['delta']);

			$dm = current($this->temporary['colors']['delta']);
			$dc = key($this->temporary['colors']['delta']);

			for($y = 0; $y < $height; $y++) {
				for($x = 0; $x < $width; $x++) {
					$this->temporary['colors']['shadow'] = imagecolorat($this->temporary['images']['shadow'], $x, $y);

					switch($dc) {
						case 'r':
							$cv = ($this->temporary['colors']['shadow'] >> 16) & 0xFF;
							break;
						case 'g':
							$cv = ($this->temporary['colors']['shadow'] >> 8) & 0xFF;
							break;
						case 'b':
							$cv = $this->temporary['colors']['shadow'] & 0xFF;
							break;
					}

					$c = abs($cv - $background[$dc]);
					$a = ($dm > 0) ? 127 - round(127 * ($c / $dm)) : 127;

					if($a < 127) {
						$ac = \Glue\Helper\Converter::rgb2color($color['r'], $color['g'], $color['b'], $a);

						imagesetpixel($this->temporary['images']['final'], $x + $offset['shadow']['x'], $y + $offset['shadow']['y'], $ac);
					}

					unset($cv, $c, $a, $ac);
				}

				unset($x);
			}

			imagecopy($this->temporary['images']['final'], $this->image, $offset['final']['x'], $offset['final']['y'], 0, 0, $this->width, $this->height);


			$this->_set($this->temporary['images']['final'], $width + abs($offset->x), $height + abs($offset->y));

			unset($color, $background, $alpha, $angle, $distance, $size, $spread, $result, $offset, $width, $height, $i, $dm, $dc, $y);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}


	/**
	 * Method to directly output the image
	 *
	 * @param string $type [optional]
	 * @param bool $interlace [optional]
	 * @param int $quality [optional]
	 * @param int $filter [optional]
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function show($type = 'png', $interlace = false, $quality = NULL, $filter = NULL) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$type'      => array($type, 'isString', array('matchesPattern', array('^png|jpeg|gif$', 'i'))),
			'$interlace' => array($interlace, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			header('Content-type: image/' . $type);
			echo $this->get($type, $interlace, $quality, $filter);

			unset($type, $interlace, $quality, $filter, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to fetch the image as binary
	 *
	 * @param string $type [optional]
	 * @param bool $interlace [optional]
	 * @param int $quality [optional]
	 * @param int $filter [optional]
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function get($type = 'png', $interlace = false, $quality = NULL, $filter = PNG_ALL_FILTERS) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$type'      => array($type, 'isString', array('matchesPattern', array('^png|jpeg|gif$', 'i'))),
			'$interlace' => array($interlace, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$type = strtolower($type);

			if($interlace === true) {
				imageinterlace($this->image, 1);
			}

			ob_start();

			switch($type) {
				case 'png':
					$quality = ($quality === NULL) ? 9 : max(0, min(9, (int) $quality));

					imagepng($this->image, NULL, $quality, $filter);
					break;
				case 'jpeg':
					$quality = ($quality === NULL) ? 100 : max(0, min(100, (int) $quality));

					imagejpeg($this->image, NULL, $quality);
					break;
				case 'gif':
					$quality = ($quality === NULL) ? 255 : max(0, min(255, (int) $quality));
					$temp    = imagecreatetruecolor($this->width, $this->height);

					imagecopy($temp, $this->image, 0, 0, 0, 0, $this->width, $this->height);
					imagetruecolortopalette($temp, false, $quality);
					imagecolormatch($this->image, $temp);
					imagegif($temp);

					unset($temp);

					break;
			}

			unset($type, $interlace, $quality, $filter, $result);

			return trim(ob_get_clean());
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to fetch the image as resource
	 *
	 * @return resource
	 */
	public function &getResource() {
		return $this->image;
	}

	private function _set(&$image, $width = NULL, $height = NULL) {
		$width  = ($width === NULL) ? $this->width : $width;
		$height = ($height === NULL) ? $this->height : $height;

		if($this->image !== NULL) {
			imagedestroy($this->image);
		}

		$this->image = imagecreatetruecolor($width, $height);

		imagealphablending($this->image, false);
		imagesavealpha($this->image, true);
		imagecopy($this->image, $image, 0, 0, 0, 0, $width, $height);

		$this->width  = $width;
		$this->height = $height;

		$this->_clean();
	}

	private function _clean() {
		if($this->temporary !== NULL) {
			foreach($this->temporary['colors'] as $k => $v) {
				unset($this->temporary['colors'][$k]);
			}

			foreach($this->temporary['images'] as $k => $v) {
				imagedestroy($v);
				unset($this->temporary['images'][$k]);
			}
		}
	}
}
?>