<?php
namespace Glue\Helper;

/**
 * Helper for general date/time operations
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
class Datetime {
	/**
	 * Method to get the current age from a date
	 *
	 * @param string $birthday
	 * @param int $base [optional]
	 *
	 * @return mixed
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	public static function getAge($birthday, $base = -INF) {
		if(($result = \Glue\Helper\validator::batch(array(
			'$birthday' => array($birthday, 'isString', 'isDate'),
			'$base'     => array($base, 'isNumeric')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			$base     = ($base === -INF) ? time() : (int) $base;
			$birthday = strtotime($birthday, $base);
			$return   = (date('m-d', $birthday) <= date('m-d', $base)) ? date('Y', $base) - date('Y', $birthday) : date('Y', $base) - date('Y', $birthday) - 1;

			unset($birthday, $base, $result);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
