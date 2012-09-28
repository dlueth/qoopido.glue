<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.age.php
 * Purpose:  calculate age
 *
 * Author:   Dirk Lüth <dlskynet@gmx.de>
 * Version:  1.0.0
 * Internet: http://www.ministry.de
 *
 * Changelog:
 * 2011-12-15 Initial release
 * -------------------------------------------------------------
 */
function smarty_block_age($parameters, $buffer, Smarty_Internal_Template $template, &$repeat) {
	if(!$repeat && isset($buffer)){
		return \Glue\Helper\Datetime::getAge($buffer);
	}
}
?>