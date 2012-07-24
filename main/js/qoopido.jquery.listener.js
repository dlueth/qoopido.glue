/*
 * jQuery extension adding support to de- and re-attach event listeners
 *
 * Copyright (c) 2012 Dirk Lüth
 *
 * Dual licensed under the MIT and GPL licenses.
 *  - http://www.opensource.org/licenses/mit-license.php
 *  - http://www.gnu.org/copyleft/gpl.html
 *
 * @author Dirk Lüth <info@qoopido.de>
 */
;(function($, window, document, undefined) {
	'use strict';

	$.listener = {
		detach: function(element, event) {
			element  = $(element);

			var events   = element.data('events') || {},
				// events = $.extend(true, {}, element.data('events'))
				detached = element.data('jquery-extension-listener') || {};

			if(typeof detached[event] === 'undefined') {
				detached[event] = { jquery: [], dom: null };
			}

			if(typeof events[event] !== 'undefined') {
				for(var listener in events[event]) {
					if(typeof events[event][listener] === 'object') {
						detached[event].jquery.push(events[event][listener]);
					}

					element.off(event, events[event][listener]);
				}
			}

			detached[event].dom = element.prop('on' + event);
			element.removeProp('on' + event).removeAttr('on' + event).data('jquery-extension-listener', detached);
		},
		attach: function(element, event) {
			element  = $(element);

			var detached = element.data('jquery-extension-listener') || {};

			if(typeof detached[event] !== 'undefined') {
				for(var listener in detached[event].jquery) {
					element.on(event, detached[event].jquery[listener]);
				}

				element.attr('on' + event, detached[event].dom).prop('on' + event, detached[event].dom);

				delete(detached[event]);

				element.data('jquery-extension-listener', detached);
			}
		}
	};
})(jQuery, window, document);