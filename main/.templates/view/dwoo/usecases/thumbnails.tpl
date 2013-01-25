{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Dynamically adjust images</h6>
			<h1>Advanced image manipulation included</h1>
		</hgroup>

		<p>
			As website layouts very often tend to need differently sized or even manipulated representations of the same image Qoopido Glue comes with it's very own image class. The class itself is integrated in the form of a chainable entity.
		</p>

		<p>
			Image manipulation can either be done in the controller or directly in the template via built-in plugins for Smarty and Dwoo (which also deal with filesystem caching).
		</p>

		<section>
			<h5>Controller code</h5>

			<p>The controller example is directly taken from the source of the controller of this page. Keep in mind that the image created is neither used nor cached in this example but both is definitely recommended in real world usage.</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/`$environment.slug`.php"}
			]]></script>
		</section>

		<section>
			<h5>Template Code</h5>

			<p>
				Again, the template source is borrowed from the source of this template directly.
			</p>

			{literal}
				<script type="syntaxhighlighter" class="brush: html"><![CDATA[
				{image return="tag:relative" resize="210,2" crop="210,210"}{url}img/lemon.jpg{/url}{/image}
				{image return="tag:relative" resize="210,2" crop="210,210" sharpen=true}{url}img/lemon.jpg{/url}{/image}
				{image return="tag:relative" resize="210,2" crop="210,210" blur=true}{url}img/lemon.jpg{/url}{/image}
				{image return="tag:relative" resize="210,2" crop="210,210" grayscale=true}{url}img/lemon.jpg{/url}{/image}
				]]></script>
			{/literal}
		</section>

		<section>
			<h5>Final output</h5>

			<p>
				The image to the left is the original image resized and cropped, various manipulations are applied to other images
			</p>

			{image return="tag:relative" lazy="true" resize="210,2" crop="210,210"}{url}img/lemon.jpg{/url}{/image}
			{image return="tag:relative" lazy="true" resize="210,2" crop="210,210" sharpen=true}{url}img/lemon.jpg{/url}{/image}
			{image return="tag:relative" lazy="true" resize="210,2" crop="210,210" blur=true}{url}img/lemon.jpg{/url}{/image}
			{image return="tag:relative" lazy="true" resize="210,2" crop="210,210" grayscale=true}{url}img/lemon.jpg{/url}{/image}
		</section>
	</article>
{/block}