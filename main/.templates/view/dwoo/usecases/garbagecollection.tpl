{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Swiss army knife for temporary files</h6>
			<h1>Cleaning up using garbagecollection</h1>
		</hgroup>

		<p>
			For Qoopido Glue itself eventually uses the filesystem for it's own caching system and there might be other occasions where unneeded files may be left over the framework comes with it's own garbagecollection.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				The Garbagecollection can be initialized in any controller and binds itself to Qoopido Glue's event system to be able to process garbage collection after any content is already output to the client.
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/.examples/`$environment.slug`.php"}
			]]></script>
		</section>
	</article>
{/block}