<?php
namespace Glue\Interfaces;

/**
 * Interface for all gateways
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
interface Gateway {
	/**
	 * Method to set an adapter for the gateway
	 *
	 * @param string $adapter
	 */
	public function setAdapter($adapter);
}
