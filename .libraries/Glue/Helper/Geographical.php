<?php
namespace Glue\Helper;

/**
 * Helper for general geographical operations
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Geographical {
	const METHOD_CIRCLE        = 1;
	const METHOD_COSINES       = 2;
	const METHOD_HAVERSINE     = 3;
	const METHOD_VINCENTY      = 4;
	const RADIUS_METER         = 6371000.785;
	const SEMIAX_MAJOR         = 6378137;
	const SEMIAX_MINOR         = 6356752.3141;
	const FACTOR_DEG2RAD       = 0.0174532925199;

	/**
	 * Method to convert a point in longitude and latitude to cartesian coordinates
	 *
	 * @param float $longitude
	 * @param float $latitude
	 *
	 * @return array
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getCartesian($longitude, $latitude) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$longitude' => array($longitude, 'isNumeric'),
			'$latitude' => array($latitude, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$lambda    = $longitude * pi() / 180;
			$phi       = $latitude * pi() / 180;

			$return = array(
				'x' => self::RADIUS_METER * cos($phi) * cos($lambda),
				'y' => self::RADIUS_METER * cos($phi) * sin($lambda),
				'z' => self::RADIUS_METER * sin($phi)
			);

			unset($longitude, $latitude, $result, $lambda, $phi);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get the distance between two points in longitude and latitude in meters according to a given method
	 *
	 * @param float $lat1
	 * @param float $lng1
	 * @param float $lat2
	 * @param float $lng2
	 * @param int $method [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getDistanceByLatLng($lat1, $lng1, $lat2, $lng2, $method = self::METHOD_COSINES) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$lat1'   => array($lat1, 'isNumeric'),
			'$lng1'   => array($lng1, 'isNumeric'),
			'$lat2'   => array($lat2, 'isNumeric'),
			'$lng2'   => array($lng2, 'isNumeric'),
			'$method' => array($method, 'isNumeric', array('isBetween', array(1, 4)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;

			switch($method) {
				case self::METHOD_CIRCLE:
					$lat1   *= self::FACTOR_DEG2RAD;
					$lng1   *= self::FACTOR_DEG2RAD;
					$lat2   *= self::FACTOR_DEG2RAD;
					$lng2   *= self::FACTOR_DEG2RAD;

					$return = rad2deg(acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2))) * 60 * 1852;

					break;
				case self::METHOD_COSINES:
					$lat1   *= self::FACTOR_DEG2RAD;
					$lng1   *= self::FACTOR_DEG2RAD;
					$lat2   *= self::FACTOR_DEG2RAD;
					$lng2   *= self::FACTOR_DEG2RAD;

					$return = self::RADIUS_METER * acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lng1 - $lng2));

					break;
				case self::METHOD_HAVERSINE:
					$lat1   *= self::FACTOR_DEG2RAD;
					$lng1   *= self::FACTOR_DEG2RAD;
					$lat2   *= self::FACTOR_DEG2RAD;
					$lng2   *= self::FACTOR_DEG2RAD;
					$dlat    = $lat2 - $lat1;
					$dlng    = $lng2 - $lng1;

					$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);

					$return = self::RADIUS_METER * (2 * atan2(sqrt($a), sqrt(1 - $a)));

					unset($dlat, $dlng, $a);

					break;
				case self::METHOD_VINCENTY:
					$lat1   *= self::FACTOR_DEG2RAD;
					$lng1   *= self::FACTOR_DEG2RAD;
					$lat2   *= self::FACTOR_DEG2RAD;
					$lng2   *= self::FACTOR_DEG2RAD;
					$f       = (self::SEMIAX_MAJOR - self::SEMIAX_MINOR) / self::SEMIAX_MAJOR;
					$L       = $lng2 - $lng1;
					$U1      = atan((1 - $f) * tan($lat1));
					$U2      = atan((1 - $f) * tan($lat2));
					$sinU1   = sin($U1);
					$sinU2   = sin($U2);
					$cosU1   = cos($U1);
					$cosU2   = cos($U2);
					$lambda  = $L;
					$lambdaP = 2 * pi();
					$i       = 20;

					while(abs($lambda - $lambdaP) > 1e-12 and --$i > 0) {
						$sinLambda = sin($lambda);
						$cosLambda = cos($lambda);
						$sinSigma  = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) + ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));

						if($sinSigma == 0) {
							return 0;
						}

						$cosSigma   = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
						$sigma      = atan2($sinSigma, $cosSigma);
						$sinAlpha   = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
						$cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
						$cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;

						if(is_nan($cos2SigmaM)) {
							$cos2SigmaM = 0;
						}

						$c       = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
						$lambdaP = $lambda;
						$lambda  = $L + (1 - $c) * $f * $sinAlpha * ($sigma + $c * $sinSigma * ($cos2SigmaM + $c * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
					}

					if($i == 0) {
						return false;
					}

					$uSq        = $cosSqAlpha * (self::SEMIAX_MAJOR * self::SEMIAX_MAJOR - self::SEMIAX_MINOR * self::SEMIAX_MINOR) / (self::SEMIAX_MINOR * self::SEMIAX_MINOR);
					$A          = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
					$B          = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
					$deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));

					$return = self::SEMIAX_MINOR * $A * ($sigma - $deltaSigma);

					unset($f, $L, $U1, $U2, $sinU1, $sinU2, $cosU1, $cosU2, $lambda, $lambdaP, $i, $sinLambda, $cosLambda, $sinSigma, $cosSigma, $sigma, $sinAlpha, $cosSqAlpha, $cos2SigmaM, $c, $uSq, $A, $B, $deltaSigma);

					break;
			}

			unset($lat1, $lng1, $lat2, $lng2, $method, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get the distance between two points in cartesion coordinates
	 *
	 * @param float $x1
	 * @param float $y1
	 * @param float $z1
	 * @param float $x2
	 * @param float $y2
	 * @param float $z2
	 *
	 * @return float
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getDistanceByCartesian($x1, $y1, $z1, $x2, $y2, $z2) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$x1'   => array($x1, 'isNumeric'),
			'$y1'   => array($y1, 'isNumeric'),
			'$z1'   => array($z1, 'isNumeric'),
			'$x2'   => array($x2, 'isNumeric'),
			'$y2'   => array($y2, 'isNumeric'),
			'$z2'   => array($z2, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = 2 * self::RADIUS_METER *
				asin(
					sqrt(
						pow($x1 - $x2, 2)
						+ pow($y1 - $y2, 2)
						+ pow($z1 - $z2, 2)
					) / (2 * self::RADIUS_METER)
				);

			unset($x1, $y1, $z1, $x2, $y2, $z2, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
