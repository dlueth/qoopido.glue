<?php
namespace Glue\Entity\Cache;

/**
 * Cache class for APC
 *
 * @require PHP "APC" extension
 *
 * @event glue.entity.cache.hit.apc(string $cid, mixed $data) > get()
 * @event glue.entity.cache.miss.apc(string $cid) > get()
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
final class Apc extends \Glue\Entity\Cache\Abstracts\Base {
	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('apc') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'APC'), EXCEPTION_EXTENSION_MISSING));
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
			return unserialize(apc_fetch(sha1($this->cid)));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
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
			return apc_store(sha1($this->cid), serialize($data), ($this->lifetime !== NULL) ? $this->timestamp + $this->lifetime : NULL);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
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
			return apc_delete(sha1($this->cid));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>