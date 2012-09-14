{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Flexibility to the maximum</h6>
			<h1>Regular expression powered routing</h1>
		</hgroup>

		<p>
			Qoopido Glue offers a very flexible URL routing powered by regular expressions. Everything that is possible using regular expressions can be routed! Routes are framework internal redirects which will not change the URL but be mapped internally. For that reason it is not only possible to redirect single pages but also to redirect complete processes. Follow one of the following links to see routing in action:
		</p>

        <p>
            If you need a site in multiple languages and prefer to have localized paths that still issue the same controllers and templates simply enable i18n in the configuration.
        </p>
		
		<ol>
			<li><a href="{url scope="global"}/route/first/{/url}">First route, no further parameters</a></li>
			<li><a href="{url scope="global"}/route/second/{/url}">Second route, no further parameters</a></li>
			<li><a href="{url scope="global"}/route/first/parameter_one/parameter_two{/url}">First route, with parameters</a></li>
		</ol>

		<section>
			<h5>Sample output</h5>

			<script type="syntaxhighlighter" class="brush: plain"><![CDATA[
			{$message}
			]]></script>
		</section>

		<section>
			<h5>Sample configuration</h5>

			<p>
				If you take a close look at the following example configuration you will most likely spot the "methods" attribute. This attribute is used to limit routes to certain http methods if present. You cannot only limit a route to one method but the value can be a comma separated list of methods. If "methods" is not present a route will be matched on any method of the request.
			</p>

			<script type="syntaxhighlighter" class="brush: xml"><![CDATA[
			<Routing enabled="true" i18n="true">
				<route methods="get">
					<pattern>(?:/route)/(first|second)(?:|/((?:[\w]+(?:/|)))*)</pattern>
					<target>/use-cases/routing?example=\1&amp;parameters=\2</target>
				</route>
			</Routing>
			]]></script>
		</section>

		<section>
			<h5>Controller code</h5>
			<p>
				The controller of this page shows a very basic example for a simple route and its processing:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/`$environment.slug`.class.php"}
			]]></script>
		</section>
	</article>
{/block}