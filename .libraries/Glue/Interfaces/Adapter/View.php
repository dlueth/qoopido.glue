<?php
namespace Glue\Interfaces\Adapter;

/**
 * Interface for all view adapter
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
interface View {
	/**
	 * Method to fetch the view's output
	 */
	public function fetch();
}
