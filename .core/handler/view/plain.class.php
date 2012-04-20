<?php
namespace Glue\Handler\View;

/**
 * View handler for text/plain
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
class Plain extends \Glue\Abstracts\Handler\View {
	/**
	 * Method to fetch the view's output
	 *
	 * @throw \RuntimeException
	 */
	public function fetch() {
		try {
			return (string) $this->adapter->get('data');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
?>