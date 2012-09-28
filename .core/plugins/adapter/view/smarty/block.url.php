<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.url.php
 * Purpose:  creates a clean URL
 *
 * Author:   Dirk Lüth <info@qoopido.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-12-15 Initial release
 * -------------------------------------------------------------
 */
function smarty_block_url($parameters, $buffer, Smarty_Internal_Template $template, &$repeat) {
	if(!$repeat && isset($buffer)){
		$scope     = (isset($parameters['scope'])) ? $parameters['scope'] : 'local';
		$separator = (isset($parameters['separator'])) ? $parameters['separator'] : '&amp;';
		$anchor    = (isset($parameters['anchor'])) ? $parameters['anchor'] : NULL;

		unset($parameters['scope']);
		unset($parameters['separator']);
		unset($parameters['anchor']);

		return \Glue\Helper\Url::make($buffer, $scope, $parameters, $anchor, $separator);
	}
}
?>