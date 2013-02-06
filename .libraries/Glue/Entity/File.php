<?php
namespace Glue\Entity;

/**
 * Entity for unified access to uploaded files
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class File {
	/**
	 * Property to store path
	 */
	protected $path;

	/**
	 * Property to store filename
	 */
	protected $filename;

	/**
	 * Property to store mimetype
	 */
	protected $mimetype;

	/**
	 * Property to store filesize
	 */
	protected $size;

	/**
	 * Property to store upload status
	 */
	protected $status;

	/**
	 * Class constructor
	 *
	 * @param string $path
	 * @param string $filename [optional]
	 * @param string $mimetype [optional]
	 * @param int $size [optional]
	 * @param int $status [optional]
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public function __construct($path, $filename = NULL, $mimetype = NULL, $size = NULL, $status = 0) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$path' => array($path, 'isString', 'isPathValid')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$this->path     = (string) $path;
			$this->filename = ($filename !== NULL) ? (string) $filename : basename($this->path);
			$this->mimetype = ($mimetype !== NULL) ? (string) $mimetype : NULL;
			$this->size     = ($size !== NULL) ? (int) $size : filesize($this->path);
			$this->status   = (int) $status;

			if($this->mimetype === NULL) {
				$this->mimetype = \Glue\Helper\Filesystem::getMimetype($this->path);
			}

			unset($path, $filename, $mimetype, $size, $status, $result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Magic mathod to retrieve restricted properties
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property) {
		if(isset($this->$property)) {
			return $this->$property;
		}
	}

	/**
	 * Magic method for checking the existance of unknown or restricted properties
	 *
	 * @param string $property
	 *
	 * @return bool
	 */
	final public function __isset($property) {
		return isset($this->$property);
	}
}
