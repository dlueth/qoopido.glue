{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>An extraordinary concept</h6>
			<h1>System wide event notification</h1>
		</hgroup>

		<p>
			Unlike other frameworks Qoopido Glue has a full fledged event system deeply integrated into it's core. It uses a dispatcher class for all kinds of events which you can bind custom listeners to. Creating and dispatching custom events as well as attaching listeners to them is a piece of cake.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				Have a look at the controller for this page. Although this controller does not make sense at all in a practical way it shows the use of system and custom events quite well. In fact the example for system events is directly how all core components register themselves with any view. Have a look at "onEvent" and you will see that it is not only possible to register to a single event but also to multiple or even a complete tree of events.
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/`$environment.slug`.class.php"}
			]]></script>
		</section>

		<section>
			<h5>Results assigned to the template</h5>

			<script type="syntaxhighlighter" class="brush: plain"><![CDATA[
				Result: {$result}
				Delta:  {$delta}
				Events:
				{foreach $events as $event}
  - {$event}
				{/foreach}
			]]></script>
		</section>
	</article>
{/block}