<?php
namespace Glue\Interfaces\Handler;

/**
 * Interface for all view handler
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
interface View {
	/**
	 * Method to fetch the view's output
	 */
	public function fetch();
}
?>