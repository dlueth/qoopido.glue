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
	const MATCH_FILES       = 1;
	const MATCH_DIRECTORIES = 2;
	const FILE_CREATE       = 4;
	const FILE_APPEND       = 8;
	const MODE_RECURSIVE    = 16;
	const MODE_ABSOLUTE     = 32;


	const MATCH_ALL         = 3;
	const FILE_ALL          = 12;
	const MODE_ALL          = 48;

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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$directory' => array($directory, 'isNotEmpty', 'isPathAllowed')
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
	 * Method to get contents of a directory
	 *
	 * @param string $directory
	 * @param string $pattern [optional]
	 * @param int $flags [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getContents($directory, $pattern = NULL, $flags = self::MATCH_ALL) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$directory' => array($directory, 'isNotEmpty', 'isPathValid'),
			'$pattern'   => ($pattern === NULL) ? NULL : array($pattern, 'isString', 'isNotEmpty'),
			'$flags'     => array($flags, array('matchesBitmask', array($flags, self::MATCH_ALL | self::FILE_ALL | self::MODE_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return    = array();
			$directory = \Glue\Helper\Modifier::cleanPath($directory);
			$iterator  = ($flags & self::MODE_RECURSIVE) ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
			$iterator  = ($pattern !== NULL) ? new \RegexIterator($iterator, '/' . $pattern . '/i') : $iterator;
			$directory = preg_quote($directory . '/', '/');

			foreach($iterator as $path) {
				if( (($flags & self::MATCH_FILES) && $path->isFile() === true) || (($flags & self::MATCH_DIRECTORIES) && $path->isDir() === true)) {
					$return[] = ($flags & self::MODE_ABSOLUTE) ? $path->getPathname() : preg_replace('/^' . $directory . '/', '', $path->getPathname());
				}
			}

			sort($return);

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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$directory' => array($directory, 'isNotEmpty', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$directory = \Glue\Helper\Modifier::cleanPath($directory);
			$iterator  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::CHILD_FIRST );

			foreach($iterator as $path) {
				if($path->isFile() === true) {
					self::removeFile($path->getPathname());
				} else if($path->isDir() === true) {
					self::removeDirectory($path->getPathname());
				}
			}

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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$directory' => array($directory, 'isNotEmpty', 'isPathAllowed')
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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$file'    => array($file, 'isNotEmpty', 'isPathAllowed'),
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
	 * @param int $flags [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function updateFile($file, $content, $flags = self::FILE_CREATE) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$file'    => array($file, 'isNotEmpty', 'isPathAllowed'),
			'$content' => array($content, 'isString'),
			'$flags'   => array($flags, array('matchesBitmask', array($flags, self::FILE_ALL)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(!is_file($file) && ($flags & self::FILE_CREATE)) {
				if(self::createFile($file, $content) === true) {
					$return = true;
				}
			} else {
				if(is_file($file)) {
					if($flags & self::FILE_APPEND) {
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

			unset($file, $content, $result);

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
	 * @param int $flags [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function appendFile($file, $content, $flags = self::FILE_CREATE) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$file'    => array($file, 'isNotEmpty', 'isPathAllowed'),
			'$content' => array($content, 'isString'),
			'$flags'   => array($flags, array('matchesBitmask', array($flags, self::FILE_CREATE)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			unset($result);

			return self::updateFile($file, $content, $flags | self::FILE_APPEND);
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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$source' => array($source, 'isNotEmpty', 'isPathValid'),
			'$target' => array($target, 'isNotEmpty', 'isPathAllowed')
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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$source' => array($source, 'isNotEmpty', 'isPathValid'),
			'$target' => array($target, 'isNotEmpty', 'isPathAllowed')
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
		if(($result = \Glue\Helper\Validator::batch(array(
			'$file' => array($file, 'isNotEmpty', 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

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
	 * @param int $flags [optional]
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function touchFile($file, $flags = 0) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$file'  => array($file, 'isNotEmpty', 'isPathAllowed'),
			'$flags' => array($flags, array('matchesBitmask', array($flags, self::FILE_CREATE)))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$return = false;
			$file   = \Glue\Helper\Modifier::cleanPath($file);

			if(!is_file($file) && ($flags & self::FILE_CREATE)) {
				self::createFile($file);
			}

			if(is_file($file)) {
				if(touch($file)) {
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

		if(($result = \Glue\Helper\Validator::batch(array(
			'$file'       => array($file, 'isNotEmpty', 'isPathValid')
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
