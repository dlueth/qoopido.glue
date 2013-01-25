<?php
namespace Glue\Entity\Cache;

/**
 * Cache class for files
 *
 * @event glue.entity.cache.hit.file(string $cid, mixed $data) > get()
 * @event glue.entity.cache.miss.file(string $cid) > get()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class File extends \Glue\Entity\Cache\Abstracts\Base {
	/**
	 * Property to store mode
	 */
	private $mode = 'serialize';

	/**
	 * Class constructor
	 *
	 * @param string $cid
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function __initialize($cid) {
		$cid = (is_string($cid)) ? \Glue\Helper\Modifier::cleanPath($cid) : $cid;

		if(($result = \Glue\Helper\validator::batch(array(
			'$cid' => array($cid, 'isPathAllowed')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}

		try {
			$this->cid       = $cid;
			$this->eventHit  = preg_replace('/\.(\w+)$/', '.hit.\1', $this->id);
			$this->eventMiss = preg_replace('/\.(\w+)$/', '.miss.\1', $this->id);

			unset($result);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set mode
	 *
	 * @param string $mode
	 *
	 * @return object
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function setMode($mode) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$mode' => array($mode, 'isString', array('matchesPattern', array('^serialize|raw$', 'i')))
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), EXCEPTION_PARAMETER));
		}


		try {
			$this->mode = strtolower($mode);

			unset($mode, $result);

			return $this;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to get a cache
	 *
	 * @return mixed
	 *
	 * @throw \RuntimeException
	 */
	final protected function _get() {
		try {
			switch($this->mode) {
				case 'serialize':
					if(file_exists($this->cid)) {
						return unserialize(file_get_contents($this->cid));
					} else {
						return false;
					}
					break;
				case 'raw':
					if(file_exists($this->cid . '.status') && file_exists($this->cid)) {
						return array(
							'status'  => unserialize(@file_get_contents($this->cid . '.status')),
							'content' => @file_get_contents($this->cid)
						);
					} else {
						return false;
					}
				break;
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to set a cache
	 *
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	final protected function _set(array $data) {
		try {
			switch($this->mode) {
				case 'serialize':
					return \Glue\Helper\Filesystem::updateFile($this->cid, serialize($data), true);
					break;
				case 'raw':
					if(\Glue\Helper\Filesystem::updateFile($this->cid . '.status', serialize($data['status']), true)	&& \Glue\Helper\Filesystem::updateFile($this->cid, $data['content'], true)) {
						return true;
					} else {
						try {
							\Glue\Helper\Filesystem::removeFile($this->cid . '.status');
							\Glue\Helper\Filesystem::removeFile($this->cid);
						} catch(\Exception $exception) {}

						return false;
					}

					break;
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method used to clear a cache
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	final protected function _clear() {
		try {
			switch($this->mode) {
				case 'serialize':
					return \Glue\Helper\Filesystem::removeFile($this->cid);
					break;
				case 'raw':
					if(\Glue\Helper\Filesystem::removeFile($this->cid . '.status') === true && \Glue\Helper\Filesystem::removeFile($this->cid) === true) {
						return true;
					} else {
						return false;
					}
					break;
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>