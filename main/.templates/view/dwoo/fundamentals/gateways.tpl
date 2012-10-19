{extends file='_main.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Continued abstraction</h6>
			<h1>Gateways to the world</h1>
		</hgroup>

		<p>
			Gateways are used to provide a unified and adapter independent access to certain functions. At the time of this writing only one view gateway exists in Qoopido Glue, but more might be developed in the future.
		</p>

		<p>
			In contrast to a simple interface a gateway takes abstraction one step further. Where an interface needs to be implemented by an adapter which will be directly instantiated a gateway will only instantiate the adapter at the very last stage. So the adapter might be changed at any time without having to re-process what you would normally have passed to the adapter directly.
		</p>

		<p>
			Taking the view gateway as the only one currently provided as an example you can e.g. pass data to the view at any stage in any controller and change the view adapter later without having to re-assign the data passed.
		</p>
	</article>
{/block}