<?php
namespace Glue\Module;

/**
 * Garbagecollection module
 *
 * @event glue.module.garbagecollection.process.pre() > process()
 * @event glue.module.garbagecollection.process.post(array $files) > process()
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Garbagecollection extends \Glue\Abstracts\Base {
	/**
	 * Property to store configuration
	 */
	protected $configuration = NULL;

	/**
	 * Class constructor
	 *
	 * @param array $configuration [optional]
	 *
	 * @throw \RuntimeException
	 */
	protected function __initialize($configuration = array()) {
		try {
			$this->configuration = \Glue\Helper\General::merge(array('probability' => 5, 'lifetime' => '-1 month', 'directories' => array()), (array) \Glue\Component\Configuration::getInstance()->get(__CLASS__), $configuration);

			if(function_exists('posix_getpid')) {
				mt_srand(crc32((double) (microtime() ^ posix_getpid())));
			} else {
				mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
			}

			if(mt_rand(0, 100) <= $this->configuration['probability']) {
				$this->dispatcher->addListener(array(&$this, 'process'), 'glue.core.output.post');
			}
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Method to process garbagecollection
	 *
	 * @throw \RuntimeException
	 */
	public function process() {
		try {
			$this->dispatcher->notify(new \Glue\Event($this->id . '.process.pre'));

			$this->configuration['lifetime'] = strtotime($this->configuration['lifetime']);

			$path  = \Glue\Component\Environment::getInstance()->get('path');
			$files = array();

			foreach($this->configuration['directories'] as $directory) {
				$files = array_merge($files, \Glue\Helper\Filesystem::getContents($path['global'] . '/' . $directory, NULL, \Glue\Helper\Filesystem::MATCH_FILES | \Glue\Helper\Filesystem::MODE_ALL));
				$files = array_merge($files, \Glue\Helper\Filesystem::getContents($path['local'] . '/' . $directory, NULL, \Glue\Helper\Filesystem::MATCH_FILES | \Glue\Helper\Filesystem::MODE_ALL));
			}

			foreach($files as $index => $file) {
				@clearstatcache(true, $file);

				if(fileatime($file) < $this->configuration['lifetime']) {
					\Glue\Helper\Filesystem::removeFile($file);
				} else {
					unset($files[$index]);
				}
			}

			$this->dispatcher->notify(new \Glue\Event($this->id . '.process.post', array($files)));

			unset($path, $files, $directory, $index, $file);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
