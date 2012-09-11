<!DOCTYPE html>
<html lang="{$environment.language}">
<head>
	<title>{foreach $tree.public.breadcrumb as $node}{if $node.visible == true}{if !$node@first} &rsaquo; {/if}{$node.title}{/if}{/foreach} | Qoopido Glue - rich features, small footprint</title>
	<meta charset="{$environment.characterset}" />
	<meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" />
	<meta name="viewport" content="width=device-width, minimum-scale=0.1, maximum-scale=10.0, initial-scale=1.0, user-scalable=yes" />
	<meta name="keywords" lang="{$environment.language}" content="" />
	<meta name="description" content="" />
	<meta name="language" content="{$environment.language}" />
	<meta name="robots" content="index,follow" />
	<meta name="date" content="{$environment.time|date_format:'%Y-%m-%d'}" />
	<meta name="revisit-after" content="{$environment.lifetime}" />
	<meta name="author" content="" />
	<meta name="copyright" content="" />
	<meta name="verify-v1" content="" />

	<base href="{$environment.url.absolute}" />

	<link rel="shortcut icon" href="{url}img/favicon.png{/url}" />
	<link rel="icon" href="{url}img/favicon.png{/url}" />
	<link rel="apple-touch-icon" href="{url}img/favicon.png{/url}" type="image/png" />

	<link rel="stylesheet" media="all" href="{url}css/general.css{/url}" />
	{block name=css}{/block}

	<!--[if lt IE 9]>
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.6/html5shiv.min.js"></script>
		<script type="text/javascript">
			if(typeof window.html5 === 'undefined') {
				document.write(decodeURI("%3Cscript src='{url}js/html5shiv.3.6.min.js{/url}' type='text/javascript'%3E%3C/script%3E"));
			}
		</script>
	<![endif]-->

	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	<script type="text/javascript">
		if(typeof jQuery === 'undefined') {
			document.write(decodeURI("%3Cscript src='{url}js/jquery.1.8.1.min.js{/url}' type='text/javascript'%3E%3C/script%3E"));
		}
	</script>

	{uglify}
		<script type="text/javascript" src="{url}js/qoopido-jquery/qoopido.base.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido-jquery/extensions/qoopido.jquery.listener.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido-jquery/functions/qoopido.jquery.function.quid.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido-jquery/plugins/qoopido.jquery.plugin.emerge.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido-jquery/plugins/qoopido.jquery.plugin.lazyimage.js{/url}"></script>
		<script type="text/javascript" src="{url}js/general.js{/url}"></script>
		{block name=uglify}{/block}
	{/uglify}
	{block name=js}{/block}
</head>
<body>
	<div id="canvas">
		<header>
			<hgroup>
				<svg  xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 42 42" overflow="visible" preserveAspectRatio="none" enable-background="new 0 0 42 42" xml:space="preserve">
					<path fill="#cc3300" d="M42,12v0.353V30v12H30H12.353H12C5.373,42,0,36.628,0,30V12C0,5.373,5.373,0,12,0h18C36.627,0,42,5.373,42,12z M28.307,15.185c-3.363,6.18-7.599,8.37-7.703,13.746c-0.078,4.009,3.451,7.264,7.703,7.264s7.703-3.254,7.703-7.264C36.01,24.922,31.879,21.162,28.307,15.185z"/>
				</svg>
				<h1>Qoopido Glue</h1>
				<h6>rich features, small footprint</h6>
			</hgroup>

			<nav>
				<ul>
					{foreach $tree.public.childnodes as $node}
						{if $node.visible == true}
							{if $node.status == 0}
								<li><a href="{url scope=global}{$node.slug}{/url}">{$node.title}</a></li>
							{else}
								<li class="active"><a href="{url scope=global}{$node.slug}{/url}">{$node.title}</a></li>
							{/if}
						{/if}
					{/foreach}
				</ul>
			</nav>
		</header>

		<section id="content">
			{if isset($tree.public.current.childnodes)}
				<nav>
					<h6>Further topics</h6>
					<ul>
						{foreach $tree.public.current.childnodes as $node}
							{if $node.visible == true}
								<li><a href="{url scope=global}{$node.slug}{/url}">{$node.title}</a></li>
							{/if}
						{/foreach}
					</ul>
				</nav>
			{/if}
			{if isset($tree.public.current.parent.childnodes)}
				<nav>
					<h6>Further topics</h6>
					<ul>
						{foreach $tree.public.current.parent.childnodes as $node}
							{if $node.visible == true}
								<li><a href="{url scope=global}{$node.slug}{/url}"{if $node.node == $environment.node} class="active"{/if}>{$node.title}</a></li>
							{/if}
						{/foreach}
					</ul>
				</nav>
			{/if}

			{block name=aside}{/block}
			{block name=content}{/block}
		</section>

		<footer>
			<nav>
				<ul>
					{foreach $tree.meta.childnodes as $node}
						{if $node.visible == true}
							<li><a href="{url scope=global}{$node.slug}{/url}">{$node.title}</a></li>
						{/if}
					{/foreach}
				</ul>
			</nav>

			<section>
				Qoopido Glue: {$core->version} | {$core->profile.duration|string_format:"%.4f"}s | {$core->profile.memory}
			</section>
		</footer>
	</div>

	{literal}
		<script type="jscript">
			try { jQuery(document).ready(); } catch(e) {}
		</script>
	{/literal}
</body>
</html>