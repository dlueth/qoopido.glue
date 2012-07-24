{extends file='_main.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Providing consistency</h6>
			<h1>Components for common abstraction</h1>
		</hgroup>

		<p>
			Components follow directly after core classes during Qoopido Glue's bootstrap process. Components, building a consistent abstraction layer in general, all share to be singleton classes. Most of them are required for internal use and therefore always get initialized but e.g. routing and session are optional.
		</p>

		<p>
			Components in general share the fact that their configuration is statically fetched via the configuration component (which gets initialized before most other components for that reason) during the bootstrap process. At the time of this writing there are components for client, configuration, environment, exception, header, request, routing, server, session and url.
		</p>
	</article>
{/block}