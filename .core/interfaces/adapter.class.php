<?php
namespace Glue\Interfaces;

/**
 * Interface for all adapter
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
interface Adapter {
	/**
	 * Method to set handler for the adapter
	 *
	 * @param string $handler
	 */
	public function setHandler($handler);
}
?>