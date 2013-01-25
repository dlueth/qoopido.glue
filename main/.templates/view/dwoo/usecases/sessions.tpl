{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Enhanced feature set</h6>
			<h1>Session handling par excellence</h1>
		</hgroup>

		<p>
			Qoopido Glue comes with it's own and enhanced implementation of PHP sessions in the form of a component. The component is enabled and configured via configuration files. Once enabled a session will be created for each visitor of your site. As usual for components your controller will be auto assigned a public member "session" which you may directly use to access or alter session data. Session data is accessed the usual way in form of a registry object.
		</p>

		<p>
			You might ask yourself what exactly might be "special" or "enhanced" compared to regular session functionality up to now. Well, Qoopido Glue's session component splits up session data into two parts:
		</p>

		<ol>
			<li>A general session container named "data"</li>
			<li>A page specific container named "page"</li>
		</ol>

		<p>
			Whatever you store in the general "data" container is available on all pages whereas data in "page" is only available for the current page. This will most likely come in handy if you, e.g., have one ore more complex, sortable lists with lots of items and a pager where you want to store user settings/positions for each individual list.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				The controller for this page gives a very basic example of how to use "data" and "page" container:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/`$environment.slug`.php"}
			]]></script>
		</section>

		<section>
			<h5>Results assigned to the template</h5>

			<script type="syntaxhighlighter" class="brush: plain"><![CDATA[
				Site visits: {$session.data.visits}
				Page visits: {$session.page.visits}
			]]></script>
		</section>
	</article>
{/block}