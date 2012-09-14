{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Extended PDO features</h6>
			<h1>Database abstraction made easy</h1>
		</hgroup>

		<p>
			Database abstraction became quite easy in PHP since PDO was released. Qoopido Glue comes with it's own database module that slightly enhances and simplifies PHP's default PDO handler.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				The database module can be initialized in any controller and for multiple databases/hosts as well. The following code gives you a simple example of how to initialize and utilize the database module:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/.examples/`$environment.slug`.class.php"}
			]]></script>
		</section>
	</article>
{/block}