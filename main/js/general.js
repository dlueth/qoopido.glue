;(function($, window, document, undefined) {
	'use strict';

	$(document).ready(function() {
		$.prefetch();
		$('img[data-lazyimage]').lazyimage();
	});
})(jQuery, window, document);