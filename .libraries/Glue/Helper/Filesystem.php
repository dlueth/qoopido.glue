<?php
namespace Glue\Helper;

/**
 * Helper for filesystem operations
 *
 * @require PHP "FILEINFO" extension for getMimetype()
 * @require PHP "mime_content_type" function for getMimetype()
 * @require PHP.ini setting "mime_magic.magicfile" for getMimetype()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Filesystem {
	/**
	 * Property to store type of server operating system
	 */
	protected static $unix      = NULL;

	/**
	 * Property to store support for fileinfo extension
	 */
	protected static $fileinfo  = NULL;

	/**
	 * Property to store support for function mime_content_type
	 */
	protected static $mimetype = NULL;

	/**
	 * Property to store setting for mime_magic.magicfile
	 */
	protected static $magicfile = NULL;

	/**
	 * Static, once only constructor
	 *
	 * @throw \RuntimeException
	 */
	public static function __once() {
		try {
			self::$unix      = (bool) preg_match('/Unix/i', $_SERVER['SERVER_SOFTWARE']);
			self::$fileinfo  = extension_loaded('fileinfo');
			self::$mimetype  = function_exists('mime_content_type');
			self::$magicfile = (ini_get('mime_magic.magicfile') != false);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to create a directory
	 *
	 * @param string $directory
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function createDirectory($directory) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$directory' => array($directory, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return    = false;
			$directory = \Glue\Helper\Modifier::cleanPath($directory);

			if(!is_dir($directory)) {
				$return = mkdir($directory, 0750, true);
			}

			unset($directory, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get subdirectories of a directory
	 *
	 * @param string $directory
	 * @param bool $recursive [optional]
	 * @param string $pattern [optional]
	 * @param bool $absolute [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getDirectories($directory, $recursive = false, $pattern = NULL, $absolute = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$directory' => array($directory, 'isString', 'isNotEmpty', 'isPathValid'),
			'$recursive' => array($recursive, 'isBoolean'),
			'$pattern'   => array($pattern, 'isString', 'isNotEmpty'),
			'$absolute'  => array($absolute, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return    = array();
			$iterator  = ($recursive === false) ? new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) : new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);
			$iterator  = ($pattern !== NULL) ? new \RegexIterator($iterator, '/' . $pattern . '/i') : $iterator;
			$directory = preg_quote($directory . '/', '/');

			foreach($iterator as $path) {
				if($path->isDir() === true) {
					$return[] = ($absolute !== false) ? $path->getPathname() : preg_replace('/^' . $directory . '/', '', $path->getPathname());
				}
			}

			sort($return);

			unset($directory, $absolute, $iterator);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get files from a directory
	 *
	 * @param string $directory
	 * @param bool $recursive [optional]
	 * @param string $pattern [optional]
	 * @param bool $absolute [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getFiles($directory, $recursive = false, $pattern = NULL, $absolute = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$directory' => array($directory, 'isString', 'isNotEmpty', 'isPathValid'),
			'$recursive' => array($recursive, 'isBoolean'),
			'$pattern'   => array($pattern, 'isString', 'isNotEmpty'),
			'$absolute'  => array($absolute, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return    = array();
			$iterator  = ($recursive === false) ? new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) : new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);
			$iterator  = ($pattern !== NULL) ? new \RegexIterator($iterator, '/' . $pattern . '/i') : $iterator;
			$directory = preg_quote($directory . '/', '/');

			foreach($iterator as $path) {
				if($path->isFile() === true) {
					$return[] = ($absolute !== false) ? $path->getPathname() : preg_replace('/^' . $directory . '/', '', $path->getPathname());
				}
			}

			sort($return);

			unset($directory, $recursive, $pattern, $absolute, $iterator);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to recursively empty a directory
	 *
	 * @param string $directory
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function emptyDirectory($directory) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$directory' => array($directory, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::CHILD_FIRST );

			foreach($iterator as $path) {
				if($path->isFile() === true) {
					self::removeFile($path->getPathname());
				} else if($path->isDir() === true) {
					self::removeDirectory($path->getPathname());
				}
			}

			sort($return);

			unset($directory, $iterator);

			return true;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to remove a directory
	 *
	 * @param string $directory
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function removeDirectory($directory) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$directory' => array($directory, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return    = false;
			$directory = \Glue\Helper\Modifier::cleanPath($directory);

			if(is_dir($directory)) {
				self::emptyDirectory($directory);

				$return = rmdir($directory);
			}

			unset($directory, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to create a file
	 *
	 * @param string $file
	 * @param string $content [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function createFile($file, $content = '') {
		if(($result = \Glue\Helper\validator::batch(array(
			'$file'    => array($file, 'isString', 'isPathValid', 'isPathAllowed'),
			'$content' => array($content, 'isString')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(!is_file($file)) {
				if(!is_dir(dirname($file))) {
					self::createDirectory(dirname($file));
				}

				if(file_put_contents($file, $content, LOCK_EX) !== false) {
					$return = true;
				}
			}

			unset($file, $content, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to update a file
	 *
	 * @param string $file
	 * @param string $content
	 * @param bool $autocreate [optional]
	 * @param bool $append [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function updateFile($file, $content, $autocreate = true, $append = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$file'       => array($file, 'isString', 'isPathValid', 'isPathAllowed'),
			'$content'    => array($content, 'isString'),
			'$autocreate' => array($autocreate, 'isBoolean'),
			'$append'     => array($append, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(!is_file($file) && $autocreate == true) {
				if(self::createFile($file, $content) === true) {
					$return = true;
				}
			} else {
				if(is_file($file)) {
					if($append == true) {
						if(file_put_contents($file, $content, FILE_APPEND | LOCK_EX) !==  false) {
							$return = true;
						}
					} else {
						if(file_put_contents($file, $content, LOCK_EX) !== false) {
							$return = true;
						}
					}
				}
			}

			unset($file, $content, $autocreate, $append, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to append content to a file
	 *
	 * @param string $file
	 * @param string $content
	 * @param bool $autocreate [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function appendFile($file, $content, $autocreate = true) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$file'       => array($file, 'isString', 'isPathValid', 'isPathAllowed'),
			'$content'    => array($content, 'isString'),
			'$autocreate' => array($autocreate, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			unset($result);

			return self::updateFile($file, $content, $autocreate, true);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to copy a file
	 *
	 * @param string $source
	 * @param string $target
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function copyFile($source, $target) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$source'       => array($source, 'isString', 'isPathValid'),
			'$target'       => array($target, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$source = \Glue\Helper\Modifier::cleanPath($source);
			$target = \Glue\Helper\Modifier::cleanPath($target);

			if(is_file($source)) {
				if(is_dir($target)) {
					$target .= '/' . basename($source);
				}

				if(copy($source, $target)) {
					$return = true;
				}
			}

			unset($source, $target, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to move a file
	 *
	 * @param string $source
	 * @param string $target
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function moveFile($source, $target) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$source'       => array($source, 'isString', 'isPathValid'),
			'$target'       => array($target, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$source = \Glue\Helper\Modifier::cleanPath($source);
			$target = \Glue\Helper\Modifier::cleanPath($target);

			if(is_file($source)) {
				if(is_dir($target)) {
					$target .= '/' . basename($source);
				}

				if(rename($source, $target)) {
					$return = true;
				}
			}

			unset($source, $target, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to remove a file
	 *
	 * @param string $file
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function removeFile($file) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$file'       => array($file, 'isString', 'isPathValid', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;

			if(is_file($file)) {
				if(unlink($file)) {
					$return = true;
				}
			}

			unset($file, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to touch a file
	 *
	 * @param string $file
	 * @param bool $autocreate [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function touchFile($file, $autocreate = false) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$file'       => array($file, 'isString', 'isPathValid', 'isPathAllowed'),
			'$autocreate' => array($autocreate, 'isBoolean')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(!is_file($file) && $autocreate == true) {
				self::createFile($file);
			}

			if(is_file($file)) {
				if(touch($file)) {
					$return = true;
				}
			}

			unset($file, $autocreate, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Method to get the mimetype of a file
	 *
	 * @param string $file
	 *
	 * @return bool
	 *
	 * @throw \LogicException
	 * @throw \RuntimeException
	 */
	public static function getMimetype($file) {
		if(self::$fileinfo !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'FILEINFO'), GLUE_EXCEPTION_EXTENSION_MISSING));
		}

		if(self::$mimetype !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'function' => 'mime_content_type'), GLUE_EXCEPTION_FUNCTION_MISSING));
		}

		if(self::$magicfile !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'setting' => 'mime_magic.magicfile'), GLUE_EXCEPTION_SETTING_MISSING));
		}

		if(($result = \Glue\Helper\validator::batch(array(
			'$file'       => array($file, 'isString', 'isPathValid')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(is_file($file)) {
				$mimetype = false;

				if(self::$fileinfo === true) {
					if($fh = finfo_open(FILEINFO_MIME)) {
						$mimetype = finfo_file($fh, $file);
						finfo_close($fh);
					}

					unset($fh);
				} elseif(self::$mimetype === true) {
					$mimetype = mime_content_type($file);
				}

				if(empty($mimetype) && self::$unix === true) {
					$mimetype = shell_exec('file --brief --mime ' . $file . ' 2> /dev/null');
				}

				$mimetype = trim($mimetype);

				if(!empty($mimetype) && preg_match('/^(application|audio|image|message|multipart|text|video)\//i', $mimetype)) {
					$return = $mimetype;
				}

				unset($mimetype);
			}

			unset($file, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
