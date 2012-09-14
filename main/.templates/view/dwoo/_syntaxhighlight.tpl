{extends file='_main.tpl'}

{block name=css}
	<link rel="stylesheet" media="all" href="//alexgorbatchev.com/pub/sh/current/styles/shCore.css" />
	<link rel="stylesheet" media="all" href="//alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css" />
{/block}

{block name=js}
	<script type="text/javascript" src="//alexgorbatchev.com/pub/sh/current/scripts/shCore.js"></script>
	<script type="text/javascript" src="//alexgorbatchev.com/pub/sh/current/scripts/shAutoloader.js"></script>
{/block}

{block name=uglify}
	<script type="text/javascript" src="{url}js/syntaxhighlight.js{/url}"></script>
{/block}