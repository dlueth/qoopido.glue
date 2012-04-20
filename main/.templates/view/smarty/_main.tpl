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

	<!--[if lt IE 9]>
		<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/r23/html5.js"></script>
	<![endif]-->

	{*yui*}
		<link rel="stylesheet" media="all" href="{url}css/general.css{/url}" />
	{*/yui*}

	{block name=css}{/block}
</head>
<body>
	<div id="canvas">
		<header>
			<hgroup>
				<svg  xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 42 42" overflow="visible" preserveAspectRatio="none" enable-background="new 0 0 42 42" xml:space="preserve">
					<path style="fill: #cc3300;" d="M42,39.15c0,1.574-1.275,2.85-2.85,2.85H2.85C1.276,42,0,40.725,0,39.15V2.85C0,1.276,1.276,0,2.85,0h36.3C40.725,0,42,1.276,42,2.85V39.15z M28.001,15.4c-3.361,6.177-7.595,8.367-7.7,13.741c-0.078,4.006,3.45,7.26,7.7,7.26s7.699-3.253,7.699-7.26C35.7,25.133,31.572,21.375,28.001,15.4z" />
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

	<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    {*yui*}
		<script type="text/javascript" src="{url}js/qoopido.jquery.function.quid.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido.jquery.plugin.emerge.js{/url}"></script>
		<script type="text/javascript" src="{url}js/qoopido.jquery.plugin.lazyimage.js{/url}"></script>
        <script type="text/javascript" src="{url}js/general.js{/url}"></script>
    {*/yui*}

    {block name=js}{/block}

	{literal}
		<script type="jscript">
			try { jQuery(document).ready(); } catch(e) {}
		</script>
	{/literal}
</body>
</html>