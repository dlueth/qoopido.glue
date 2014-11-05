<?php
namespace Glue\Helper;
/*
 * Kleiner Merkzettel:
 * \w in einem regulären Ausdruck matched sprachspezifische Umlaute nur dann, 
 * wenn locale richtig gesetzt ist (und matched selbst dann aber nur die in 
 * der Sprache vorkommenden Zeichen, im deutschen z.B. NUR äöüÄAÜß - aber 
 * KEINE anderen). Daher ggf. Unicode benutzen, Beispiel: (\pL+)
 */

/**
 * Helper for general validator
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Validator {
	/**
	 * Method to batch validate
	 *
	 * @param array $batch
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException
	 */
	public static function batch(array $batch) {
		static $default = array(NULL, NULL, true);

		try {
			$return = array();

			foreach($batch as $key => $validator) {
				$each  = (preg_match('/^@/', $key) > 0) ? true : false;
				$key   = ($each === true) ? preg_replace('/^@/', '', $key) : $key;

				if($validator === NULL) {
					continue;
				}

				$value = array_shift($validator);

				foreach($validator as $v) {
					list($method, $parameter, $expected) = ((array) $v) + $default;

					$result = $expected;

					if($parameter === NULL) {
						if($each === false || !is_array($value)) {
							$result = self::$method($value);
						} else {
							foreach($value as $v) {
								$result = self::$method($v);

								if($result !== $expected) {
									$return[] = $key;
									break 2;
								}
							}
						}
					} else {
						if($each === false || !is_array($value)) {
							array_unshift($parameter, $value);
							$result = call_user_func_array(array('self', $method), $parameter);
						} else {
							foreach($value as $v) {
								$result = call_user_func_array(array('self', $method), array_merge(array($v), $parameter));

								if($result !== $expected) {
									$return[] = $key;
									break 2;
								}
							}
						}
					}

					if($result !== $expected) {
						$return[] = $key;
						break;
					}

					unset($method, $parameter, $expected, $result);
				}

				unset($each, $value, $v);
			}

			$return = (count($return) > 0) ? implode(', ', $return) : true;

			unset($batch, $key, $validator);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate a given value against a pattern
	 *
	 * @param string $value
	 * @param string $pattern
	 * @param string $options [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function matchesPattern($value, $pattern, $options = '') {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value'   => array($value, 'isScalar'),
                '$pattern' => array($pattern, 'isScalar'),
                '$options' => array($options, 'isString')
            )) !== true) {
                return false;
            }

			$return = (preg_match('/' . str_replace('/', '\/', (string) $pattern) . '/' . $options, (string) $value)) ? true : false;

			unset($value, $pattern, $options);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate a given value against a bitmask
	 *
	 * @param int $value
	 * @param int $bitmask
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function matchesBitmask($value, $bitmask) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value'   => array($value, 'isInteger', array('isGreater', array(0, true))),
                '$bitmask' => array($bitmask, 'isInteger', array('isGreater', array(0, true)))
            )) !== true) {
                return false;
            }

			$return = (($value & $bitmask) === $value);

			unset($value, $bitmask);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a valid e-mail
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isEmail($value) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString')
            )) !== true) {
                return false;
            }

			$return = false;

			if(preg_match('/^(?:[a-z0-9._-][a-z0-9\\-\\.\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\.(?:museum|[a-z]{2,4}))$/i', $value, $matches) > 0) {
				if(isset($matches[1]) && (checkdnsrr($matches[1], 'MX') === true || checkdnsrr($matches[1], 'A') === true)) {
					$return = true;
				}
			}

			unset($value, $matches);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a string
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isString($value) {
		return is_string($value);
	}

	/**
	 * Method to validate if a given value is a scalar
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isScalar($value) {
		return is_scalar($value);
	}

	/**
	 * Method to validate if a given value is an array
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isArray($value) {
		return is_array($value);
	}

	/**
	 * Method to validate if a given value is a resource
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isResource($value) {
		return is_resource($value);
	}

	/**
	 * Method to validate if a given value is an object
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isObject($value) {
		return is_object($value);
	}

	/**
	 * Method to validate if a given value is empty
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isEmpty($value) {
		try {
			$return = false;

			switch(gettype($value)) {
				case 'string':
					$return = empty($value);
					break;
				case 'array':
					$return = (count($value) == 0) ? true : false;
					break;
				case 'object':
					switch(get_class($value)) {
						case 'Glue\Entity\File':
							$return = ($value->status == UPLOAD_ERR_NO_FILE) ? false : true;
							break;
						default:
							$return = true;
							break;
					}
					break;
			}

			unset($value);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is not empty
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isNotEmpty($value) {
		try {
			$return = false;

			switch(gettype($value)) {
				case 'string':
					$return = !empty($value);
					break;
				case 'array':
					$return = (count($value) == 0) ? false : true;
					break;
				case 'object':
					switch(get_class($value)) {
						case 'Glue\Entity\File':
							$return = ($value->status == UPLOAD_ERR_NO_FILE) ? true : false;
							break;
						default:
							$return = true;
							break;
					}
					break;
			}

			unset($value);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a float
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isFloat($value) {
		return is_float($value);
	}

	/**
	 * Method to validate if a given value is an integer
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isInteger($value) {
		return is_int($value);
	}

	/**
	 * Method to validate if a given value is numeric
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isNumeric($value) {
		return is_numeric($value);
	}

	/**
	 * Method to validate if a given value is boolean
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function isBoolean($value) {
		return is_bool($value);
	}

	/**
	 * Method to validate if a given value is a string
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isCallback($value) {
		return is_callable($value);
	}

	/**
	 * Method to validate if a given value is a valid date
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function isDate($value) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString')
            )) !== true) {
                return false;
            }

			$return = false;

			$value = strtotime($value, \Glue\Component\Environment::getInstance()->get('time'));

			if($value !== false && $value !== -1) {
				$return = checkdate(date('m', $value), date('d', $value), date('Y', $value));
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a valid UUID
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isUUID($value) {
    	try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString')
            )) !== true) {
                return false;
            }

			return (preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $value) === 1);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a valid EAN
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isEAN($value) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString', array('matchesPattern', array('\d{13}')))
            )) !== true) {
                return false;
            }

			return ($value[12] == (((ceil(((($value[1] + $value[3] + $value[5] + $value[7] + $value[9] + $value[11]) * 3) + ($value[0] + $value[2] + $value[4] + $value[6] + $value[8] + $value[10]))/10))*10) - ((($value[1] + $value[3] + $value[5] + $value[7] + $value[9] + $value[11]) * 3) + ($value[0] + $value[2] + $value[4] + $value[6] + $value[8] + $value[10]))));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a UPC
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isUPC($value) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString', array('matchesPattern', array('\d{12}')))
            )) !== true) {
                return false;
            }

			return ($value[11] == (((ceil(((($value[0] + $value[2] + $value[4] + $value[6] + $value[8] + $value[10]) * 3) + ($value[1] + $value[3] + $value[5] + $value[7] + $value[9]))/10))*10) - ((($value[0] + $value[2] + $value[4] + $value[6] + $value[8] + $value[10]) * 3) + ($value[1] + $value[3] + $value[5] + $value[7] + $value[9]))));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if two given values are equal
	 *
	 * @param mixed $source
	 * @param mixed $target
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isEqual($source, $target) {
		try {
    		return (serialize($source) === serialize($target));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if two given values are not equal
	 *
	 * @param mixed $source
	 * @param mixed $target
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function notEqual($source, $target) {
		try {
			return (serialize($source) !== serialize($target));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is greater than another value
	 *
	 * @param mixed $source_value
	 * @param mixed $compare_value
	 * @param bool $equal [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isGreater($source_value, $compare_value, $equal = false) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$source_value'  => array($source_value, 'isScalar'),
                '$compare_value' => array($compare_value, 'isScalar'),
                '$equal'         => array($equal, 'isBoolean')
            )) !== true) {
                return false;
            }

			return ($equal === true) ? ($source_value >= $compare_value) : ($source_value > $compare_value);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is less than another value
	 *
	 * @param mixed $source_value
	 * @param mixed $compare_value
	 * @param bool $equal [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isLess($source_value, $compare_value, $equal = false) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$source_value'  => array($source_value, 'isScalar'),
                '$compare_value' => array($compare_value, 'isScalar'),
                '$equal'         => array($equal, 'isBoolean')
            )) !== true) {
                return false;
            }

			return ($equal === true) ? ($source_value <= $compare_value) : ($source_value < $compare_value);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is between a min and a max value
	 *
	 * @param mixed $value
	 * @param mixed $compare_min
	 * @param mixed $compare_max
	 * @param bool $equal [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isBetween($value, $compare_min, $compare_max, $equal = true) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value'       => array($value, 'isScalar'),
                '$compare_min' => array($compare_min, 'isScalar'),
                '$compare_max' => array($compare_max, 'isScalar'),
                '$equal'       => array($equal, 'isBoolean')
            )) !== true) {
                return false;
            }

			return ($equal === true) ? ($value >= $compare_min && $value <= $compare_max) : ($value > $compare_min && $value < $compare_max);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given values length is in a certain range
	 *
	 * @param scalar $value
	 * @param int $length_min [optional]
	 * @param int $length_max [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isLength($value, $length_min = 0, $length_max = INF) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value'      => array($value, 'isScalar'),
                '$length_min' => array($length_min, 'isNumeric', array('isGreater', array(0))),
                '$length_max' => array($length_max, 'isNumeric', array('isGreater', array($length_min, true)))
            )) !== true) {
                return false;
            }

			$length = strlen((string) $value);

			return ($length >= (int) $length_min && $length <= (int) $length_max);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value would be a secure password
	 *
	 * @param string $value
	 * @param int $length_min [optional]
	 * @param int $length_max [optional]
	 * @param int $score [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isSecure($value, $length_min = 6, $length_max = 12, $score = 0) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value'      => array($value, 'isScalar'),
                '$length_min' => array($length_min, 'isNumeric', array('isGreater', array(0))),
                '$length_max' => array($length_max, 'isNumeric', array('isGreater', array($length_min))),
                '$score'      => array($score, 'isNumeric', array('isGreater', array(0, true))),
            )) !== true) {
                return false;
            }

			$return     = false;
			$value      = (string) $value;
			$length_min = (int) $length_min;
			$length_max = (int) $length_max;

			if(self::isLength($value, $length_min, $length_max) === true) {
				$_score = 0;

				if(preg_match('/[a-z]/', $value)) {
					$_score += 1;
				}

				if(preg_match('/[A-Z]/', $value)) {
					$_score += 1;
				}

				if(preg_match('/[0-9]/', $value)) {
					$_score += 1;
				}

				if(preg_match('/[-_:+*]/', $value)) {
					$_score += 1;
				}

				if($_score >= (int) $score) {
					$return = true;
				}

				unset($_score);
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given path is local or remote
	 *
	 * @param string $path
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isLocal($path) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$path' => array($path, 'isString')
            )) !== true) {
                return false;
            }

			$info   = parse_url((string) $path);

			return isset($info['scheme']) ? false : true;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a valid path
	 *
	 * @param string $path
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isPathValid($path) {
    	try {
            if(\Glue\Helper\Validator::batch(array(
                '$path' => array($path, 'isString')
            )) !== true) {
                return false;
            }

			return (preg_match('/^(\/(?:(?:(?:(?:[a-zA-Z0-9\\-_.!~*\'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\\-_.!~*\'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*)(?:\/(?:(?:(?:[a-zA-Z0-9\\-_.!~*\'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\\-_.!~*\'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*))*))$/', (string) $path) > 0);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is allowed
	 *
	 * @param string $path
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isPathAllowed($path) {
    	try {
            if(\Glue\Helper\Validator::batch(array(
                '$path' => array($path, 'isPathValid')
            )) !== true) {
                return false;
            }

			static $expression = NULL;

			if($expression === NULL) {
				if(\Glue\Factory::getInstance()->exists('\Glue\Component\Environment') === true) {
					$paths    = \Glue\Component\Environment::getInstance()->get('path');
					$settings = \Glue\Component\Configuration::getInstance()->get(__CLASS__);

					if(isset($settings['writeable'])) {
						$expression = '/^(' . preg_quote($paths['local'], '/') . '|' . preg_quote($paths['global'], '/') . ')\/(?:' . implode('|', array_map('preg_quote', $settings['writeable'], array('/'))) . ')|' . preg_quote(realpath(sys_get_temp_dir()), '/') . '(?:\/.*|)$/';
					} else {
						$expression = false;
					}

					unset($paths, $settings);
				}
			}

			if(!empty($expression)) {
				$return = (preg_match($expression, \Glue\Helper\Modifier::cleanPath((string) $path)) === 1) ? true : false;
			} else {
				$return = true;
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given value is a valid filename
	 *
	 * @param string $value
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isFilename($value) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$value' => array($value, 'isString')
            )) !== true) {
                return false;
            }

			return (strlen($value) <= 255 && preg_match('/^(?!^(PRN|AUX|CLOCK\$|NUL|CON|COM\d|LPT\d|\..*)(\..+)?$)[^\x00-\x1f\?*:"";|\/]+$/', $value) > 0);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given file is a valid upload
	 *
	 * @param object $file
	 * @param bool $status [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isFileUpload(\Glue\Entity\File $file, $status = true) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$status' => array($status, 'isBoolean')
            )) !== true) {
                return false;
            }

			$return = false;

			if(@is_uploaded_file($file->path) === true) {
				if($status === true) {
					if($file->status == 0) {
						$return = true;
					}
				} else {
					$return = true;
				}
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given file has a certain size
	 *
	 * @param object $file
	 * @param int $min [optional]
	 * @param int $max [optional]
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isFileSize(\Glue\Entity\File $file, $min = 0, $max = INF) {
		try {
            if(\Glue\Helper\Validator::batch(array(
                '$min' => array($min, 'isNumeric', array('isGreater', array(0))),
                '$max' => array($max, 'isNumeric', array('isGreater', array($min, true)))
            )) !== true) {
                return false;
            }

			if($file->size > $min && $file->size < $max) {
				$return = true;
			} else {
				$return = false;
			}

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to validate if a given file has a certain mimetype
	 *
	 * @param object $file
	 * @param mixed $mimetype
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	public static function isMimetype(\Glue\Entity\File $file, $mimetype) {
		try {
            $mimetype = (is_string($mimetype)) ? (array) $mimetype : $mimetype;

            if(\Glue\Helper\Validator::batch(array(
                '$mimetype' => array($mimetype, 'isArray')
            )) !== true) {
                return false;
            }

			return in_array(\Glue\Helper\Filesystem::getMimetype($file->path), $mimetype);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
