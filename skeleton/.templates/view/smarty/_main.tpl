<!DOCTYPE html>
<html lang="{$environment.language}">
<head>
	<title>Project skeleton | Qoopido Glue</title>
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
</head>
<body>
	<div id="canvas">
		<div id="content">
			{block name=content}{/block}
		</div>
	</div>
</body>
</html>