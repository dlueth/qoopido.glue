{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Annoyed by handwriting complex SQL queries?</h6>
			<h1>Use the modular Querybuilder</h1>
		</hgroup>

		<p>
			In addition to the database module with only slightly enhanced features Qoopido Glue offers a very flexible, lightweight and modular Querybuilder that greatly simplifies the creation of complex queries.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				The Querybuilder is an entity and can be used in any controller. If you still have the example about database abstraction in mind here is how the controller code would look like utilizing the Querybuilder instead of directly writing the SQL:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/.examples/`$environment.slug`.php"}
			]]></script>

		</section>
	</article>
{/block}