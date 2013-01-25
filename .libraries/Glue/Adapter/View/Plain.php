<?php
namespace Glue\Adapter\View;

/**
 * View adapter for text/plain
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Plain extends \Glue\Abstracts\Adapter\View {
	/**
	 * Method to fetch the view's output
	 *
	 * @throw \RuntimeException
	 */
	public function fetch() {
		try {
			return (string) $this->gateway->get('data');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>