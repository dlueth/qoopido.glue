<?php
namespace Glue\Adapter\View;

/**
 * View adapter for XML
 *
 * @require PHP "SIMPLEXML" extension
 * @require PHP "LIBXML" extension
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Xml extends \Glue\Abstracts\Adapter\View {
	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('simplexml') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'SIMPLEXML'), GLUE_EXCEPTION_EXTENSION_MISSING));
		}

		if(extension_loaded('libxml') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'LIBXML'), GLUE_EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Method to fetch the view's output
	 *
	 * @throw \RuntimeException
	 */
	public function fetch() {
		try {
			$data = $this->gateway->get('data');

			if(is_object($data)) {
				switch(get_class($data)) {
					case 'SimpleXMLElement':
						$data = dom_import_simplexml($data)->ownerDocument;
						break;
					case 'DOMDocument':
						break;
				}
			} elseif(is_array($data)) {

			} elseif(is_string($data)) {

			}

			$data->formatOutput = true;

			return($data->saveXML());
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
