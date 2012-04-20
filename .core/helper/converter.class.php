<?php
namespace Glue\Helper;

/**
 * Helper for general conversion
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Converter {
	/**
	 * Method to convert a filesize to human readable units
	 *
	 * @param int $size
	 * @param int $base [optional]
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function bytes2human($size, $base = 1024) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$size' => array($size, 'isNumeric', array('isGreater', array(0, true))),
			'$base' => array($base, array('matchesPattern', array('1000|1024'), true))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			static $unit = array(
				1000 => array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'),
				1024 => array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')
			);

			$base   = ($base == 1000 || $base == 1024) ? (int) $base : 1024;
			$return = round($size / pow($base, ($i = floor(log($size , $base)))), 2) . ' ' . $unit[$base][$i];

			unset($size, $base, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a kilometer to miles
	 *
	 * @param int $value
	 *
	 * @return int
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function kilometer2miles($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value' => array($value, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = ((int) $value) * 0.621371192;

			unset($value, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a miles to kilometer
	 *
	 * @param int $value
	 *
	 * @return int
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function miles2kilometer($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value' => array($value, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = ((int) $value) / 0.621371192;

			unset($value, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a nautical miles to kilometer
	 *
	 * @param int $value
	 *
	 * @return int
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function nauticalmiles2kilometer($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value' => array($value, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = ((int) $value) / 0.539956803455724;

			unset($value, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from hex notation to a rgba array
	 *
	 * @param int $value
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function hex2rgb($value) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$value' => array($value, 'isString', array('matchesPattern', array('^#[a-f0-9]{3,3}|[a-f0-9]{6,6}$')))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = NULL;
			$value  = strtolower($value);

			if(preg_match('/^#[a-f0-9]{3,3}$$/', $value)) {
				$value = preg_replace('/([a-f0-9])/', '\1\1', $value);
			}

			$return = array(
				'r' => 0,
				'g' => 0,
				'b' => 0,
				'a' => 0,
			);

			sscanf(substr($value, 1), "%2x%2x%2x", $return['r'], $return['g'], $return['b']);

			unset($value, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from rgba to hex notation
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $a [optional]
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function rgb2hex($r, $g, $b, $a = 255) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$r' => array($r, 'isNumeric', array('isBetween', array(0, 255))),
			'$g' => array($g, 'isNumeric', array('isBetween', array(0, 255))),
			'$b' => array($b, 'isNumeric', array('isBetween', array(0, 255))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 255)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$r = (int) $r;
			$g = (int) $g;
			$b = (int) $b;
			$a = (int) $a;

			unset($result);

			if($a === 255) {
				return sprintf("%s%02X%02X%02X", '#', $r, $g, $b);
			} else {
				return sprintf("%s%02X%02X%02X%02X", '#', $a, $r, $g, $b);
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color resource to a rgba array
	 *
	 * @param int $color
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function color2rgb($color) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$color' => array($color, 'isInteger')
		))) !== true) {
			echo '=> ' . gettype($color) . '<br />';
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			unset($result);

			return array(
				'r' => ($color >> 16) & 0xFF,
				'g' => ($color >> 8) & 0xFF,
				'b' => $color & 0xFF,
				'a' => ($color & 0x7F000000) >> 24,
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from rgba to a color resource
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $a [optional]
	 *
	 * @return resource
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function rgb2color($r, $g, $b, $a = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$r' => array($r, 'isNumeric', array('isBetween', array(0, 255))),
			'$g' => array($g, 'isNumeric', array('isBetween', array(0, 255))),
			'$b' => array($b, 'isNumeric', array('isBetween', array(0, 255))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 255)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			unset($result);

			return ((int) $r << 16) + ((int) $g << 8) + (int) $b + ((int) $a << 24);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color resource to grayscale
	 *
	 * @param resource $color
	 *
	 * @return resource
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function color2grayscale($color) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$color' => array($color, 'isInteger')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$return = self::color2rgb($color);
			$return['r'] = $return['g'] = $return['b'] = (($return['r'] * 0.2989) + ($return['g'] * 0.5870) + ($return['b'] * 0.1140));

			unset($color, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from rgba to a yuva array
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $a [optional]
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function rgb2yuv($r, $g, $b, $a = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$r' => array($r, 'isNumeric', array('isBetween', array(0, 255))),
			'$g' => array($g, 'isNumeric', array('isBetween', array(0, 255))),
			'$b' => array($b, 'isNumeric', array('isBetween', array(0, 255))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 255)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$r /= 255;
			$g /= 255;
			$b /= 255;

			$y = ($r * 0.299 + $g * 0.587 + $b * 0.114) * 100;
			$u = (-$r * 0.1471376975169300226 - $g * 0.2888623024830699774 + $b * 0.436) * 100;
			$v = ($r * 0.615 - $g * 0.514985734664764622 - $b * 0.100014265335235378) * 100;

			unset($g, $g, $b, $result);

			return array(
				'y' => $y,
				'u' => $u,
				'v' => $v,
				'a' => (int) $a,
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from yuva to a rgba array
	 *
	 * @param int $y
	 * @param int $u
	 * @param int $v
	 * @param int $a [optional]
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function yuv2rgb($y, $u, $v, $a = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$y' => array($y, 'isNumeric', array('isBetween', array(0, 100))),
			'$u' => array($u, 'isNumeric', array('isBetween', array(0, 100))),
			'$v' => array($v, 'isNumeric', array('isBetween', array(0, 100))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 100)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$y /= 100;
			$u /= 100;
			$v /= 100;

			$r = abs(round(($y + 1.139837398373983740 * $v) * 255));
			$g = abs(round(($y - 0.3946517043589703515 * $u - 0.5805986066674976801 * $v) * 255));
			$b = abs(round(($y + 2.03211091743119266 * $u) * 255));

			unset($y, $u, $v, $result);

			return array(
				'r' => $r,
				'g' => $g,
				'b' => $b,
				'a' => (int) $a,
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from rgba to a hsla array
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @param int $a [optional]
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function rgb2hsl($r, $g, $b, $a = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$r' => array($r, 'isNumeric', array('isBetween', array(0, 255))),
			'$g' => array($g, 'isNumeric', array('isBetween', array(0, 255))),
			'$b' => array($b, 'isNumeric', array('isBetween', array(0, 255))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 255)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$r /= 255;
			$g /= 255;
			$b /= 255;

			$max = max($r, $g, $b);
			$min = min($r, $g, $b);

			$h = $s = $l = ($max + $min) / 2;

			if($max === $min) {
				$h = $s = 0;
			} else {
				$chroma = $max - $min;

				$s = ($l <= .5) ? $chroma / (2 * $l) : $chroma / (2 - 2 * $l);

				switch($max) {
					case $r:
						$h = (($g - $b) / $chroma) + ($g < $b ? 6 : 0);
						break;
					case $g:
						$h = (($b - $r) / $chroma) + 2;
						break;
					case $b:
						$h = (($r - $g) / $chroma) + 4;
						break;
				}

				$h *= 60;

				unset($chroma);
			}

			unset($r, $g, $b, $result, $max, $min);;

			return array(
				'h' => $h,
				's' => $s,
				'l' => $l,
				'a' => $a,
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a color from hsla to a rgba array
	 *
	 * @param int $h
	 * @param int $s
	 * @param int $l
	 * @param int $a [optional]
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function hsl2rgb($h, $s, $l, $a = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$h' => array($h, 'isNumeric', array('isBetween', array(0, 1))),
			'$s' => array($s, 'isNumeric', array('isBetween', array(0, 1))),
			'$l' => array($l, 'isNumeric', array('isBetween', array(0, 1))),
			'$a' => array($a, 'isNumeric', array('isBetween', array(0, 1)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			if($s === 0) {
				$r = $g = $b = $l;
			} else {
				$h /= 360;

				$q = ($l < .5) ? $l * (1 + $s) : $l + $s - $l * $s;
				$p = 2 * $l - $q;

				$r = self::hue2rgb($p, $q, $h + 1/3);
				$g = self::hue2rgb($p, $q, $h);
				$b = self::hue2rgb($p, $q, $h - 1/3);

				unset($h, $q, $p);
			}

			unset($h, $s, $l, $result);

			return array(
				'r' => $r * 255,
				'g' => $g * 255,
				'b' => $b * 255,
				'a' => $a,
			);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to convert a hue value to single r, g, b values
	 *
	 * @param int $p
	 * @param int $q
	 * @param int $t
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	private static function hue2rgb($p, $q, $t) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$p' => array($p, 'isNumeric', array('isBetween', array(0, 1))),
			'$q' => array($q, 'isNumeric', array('isBetween', array(0, 1))),
			'$t' => array($t, 'isNumeric', array('isBetween', array(0, 1)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			if($t < 0) $t++;
			if($t > 1) $t--;
			if($t < 1/6) return $p + ($q - $p) * 6 * $t;
			if($t < 1/2) return $q;
			if($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;

			unset($q, $t, $result);

			return $p;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>