jQuery(document).ready(function() {
	SyntaxHighlighter.autoloader(
		'php  http://alexgorbatchev.com/pub/sh/current/scripts/shBrushPhp.js',
		'js jscript javascript  http://alexgorbatchev.com/pub/sh/current/scripts/shBrushJScript.js',
		'css  http://alexgorbatchev.com/pub/sh/current/scripts/shBrushCss.js',
		'text plain  http://alexgorbatchev.com/pub/sh/current/scripts/shBrushPlain.js',
		'xml xhtml html  http://alexgorbatchev.com/pub/sh/current/scripts/shBrushXml.js'
	);

	SyntaxHighlighter.defaults['toolbar'] = false;
	SyntaxHighlighter.all();
	shLineWrap();
});

jQuery(window).load(function() {
});

var shLineWrap = function() {
	var elements = jQuery('.syntaxhighlighter');

	if(elements.length === 0) {
		setTimeout(shLineWrap, 800);
	} else {
		elements.each(function() {
			var $sh     = $(this),
				$gutter = $sh.find('td.gutter'),
				$code   = $sh.find('td.code');

			$gutter.children('.line').each(function(i) {
				var $gutterLine  = $(this),
					$codeLine    = $code.find('.line:nth-child(' + (i + 1) + ')'),
					codeHeight   = $.fn.actual ? ($codeLine.actual('height') || 0) : ($codeLine.height() || 0),
					gutterHeight = $.fn.actual ? ($gutterLine.actual('height') || 0) : ($gutterLine.height() || 0);

				if (codeHeight && gutterHeight && codeHeight > gutterHeight) {
					$gutterLine.css('cssText', 'height:' + codeHeight + 'px !important');
				}
			});
		});
	}
};