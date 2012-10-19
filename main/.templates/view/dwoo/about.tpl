{extends file='_main.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Complete microframework for PHP 5.3+</h6>
			<h1>Qoopido Glue</h1>
		</hgroup>

		<p>
			Having used a wide variety of PHP frameworks over the last ten years and never being really satisfied with any of them I decided to develop my own framework some time ago. I started from scratch a couple of times during development, due to the very complex nature of such a task. Finally I have reached a point where I think the framework is not only a proof of concept but can be released as is to polish it up a bit with public feedback. All of the core and components should be robust and I am almost sure their API will not change to an extent to become completely incompatible in the future.
		</p>

		<p>
			One thing I thought about for a very long time and finally came to a conclusion is if Qoopido Glue is an MVC framework by nature. I read numerous articles about what exactly the "M" in "MVC means for a PHP- or web-framework in general. Every article had its own theory about that, claiming that what the author thinks is what the majority of others do as well. Funny enough they all presented different interpretations. I than started to read on patterns in general and came to the conclusion that Qoopido Glue is what is best called a "supervising controller pattern". So it can be an MVC if you implement your own system for the model (whatever this term is for you) but is not by default.
		</p>

		<p>
			Keep in mind that Qoopido Glue is a feature-rich, light and fast microframework which main aim is rapid paired with modular development. It was never planned as and will never be a replacement for enterprise aimed frameworks like ZF for example.
		</p>
	</article>
{/block}