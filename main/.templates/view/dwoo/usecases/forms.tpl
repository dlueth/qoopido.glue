{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>A formerly complex task:</h6>
			<h1>Form handling and validation</h1>
		</hgroup>

		<p>
			Qoopido Glue has a very flexible and versatile abstraction for making handling and validation of HTML-forms a breeze. It utilizes the frameworks abstracted request component and its static modifiers/validators and is build as an entity itself.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				Forms can be defined in any type of controller which is quite straight forward. The following example code is directly taken from the controller of this page and shows ease of use perfectly:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
{fetch file="`$environment.path.local`/.controller/`$environment.slug`.php"}
			]]></script>

			<p>
				To not limit the developer more than required there is no HTML- or template-implementation but forms can - and have to be - completely custom built within the template. For security reasons forms are limited to exactly one request method, "post" in this example. Most of the field types extend a base element and add custom validators e.g. the field type "email" automatically adds an email-validation to the field.
			</p>
		</section>

		<section>
			<h5>Template code</h5>
			<p>
				The HTML template code for the form might look similar to the following example (which is taken from this page):
			</p>

			{literal}
				<script type="syntaxhighlighter" class="brush: html"><![CDATA[
					<form action="{url scope=global}{$environment.node}{/url}" method="{$example->method}">
						<fieldset>
							<legend>Form example ({if $example->sent == false}not submitted{else}submitted {if $example->valid == false}but invalid{else}and valid{/if}{/if})</legend>

							<label{if $example->elements.firstname->errors !== NULL} class="error"{/if}>
								<span>Firstname<sup>*</sup></span>
								<input type="text" name="example[firstname]" value="{$example->elements.firstname->value}" />
							</label>

							<label{if $example->elements.lastname->errors !== NULL} class="error"{/if}>
								<span>Lastname<sup>*</sup></span>
								<input type="text" name="example[lastname]" value="{$example->elements.lastname->value}" />
							</label>

							<label{if $example->elements.nickname->errors !== NULL} class="error"{/if}>
								<span>Nickname<sup>*</sup></span>
								<input type="text" name="example[nickname]" value="{$example->elements.nickname->value}" />
							</label>

							<label{if $example->elements.phone->errors !== NULL} class="error"{/if}>
								<span>Phone</span>
								<input type="text" name="example[phone]" value="{$example->elements.phone->value}" />
							</label>

							<label{if $example->elements.email1->errors !== NULL} class="error"{/if}>
								<span>Email<sup>*</sup></span>
								<input type="text" name="example[email1]" value="{$example->elements.email1->value}" />
							</label>

							<label{if $example->elements.email2->errors !== NULL} class="error"{/if}>
								<span>Repeated<sup>*</sup></span>
								<input type="text" name="example[email2]" value="{$example->elements.email2->value}" />
							</label>
						</fieldset>

						<fieldset class="buttons">
							<button type="submit">Submit form</button>
						</fieldset>
					</form>
				]]></script>
			{/literal}
		</section>

		<section>
			<h5>Example</h5>
			<p>
				The final output will exactly look and behave like the following form while this is more of a functional example and you will most likely have to adjust many aspects to your custom needs:
			</p>

			<form action="{url scope=global}{$environment.node}{/url}" method="{$example->method}">
				<fieldset>
					<legend>Form example ({if $example->sent == false}not submitted{else}submitted {if $example->valid == false}but invalid{else}and valid{/if}{/if})</legend>

					<label{if $example->elements.firstname->errors !== NULL} class="error"{/if}>
						<span>Firstname<sup>*</sup></span>
						<input type="text" name="example[firstname]" value="{$example->elements.firstname->value}" />
					</label>

					<label{if $example->elements.lastname->errors !== NULL} class="error"{/if}>
						<span>Lastname<sup>*</sup></span>
						<input type="text" name="example[lastname]" value="{$example->elements.lastname->value}" />
					</label>

					<label{if $example->elements.nickname->errors !== NULL} class="error"{/if}>
						<span>Nickname<sup>*</sup></span>
						<input type="text" name="example[nickname]" value="{$example->elements.nickname->value}" />
					</label>

					<label{if $example->elements.phone->errors !== NULL} class="error"{/if}>
						<span>Phone</span>
						<input type="text" name="example[phone]" value="{$example->elements.phone->value}" />
					</label>

					<label{if $example->elements.email1->errors !== NULL} class="error"{/if}>
						<span>Email<sup>*</sup></span>
						<input type="text" name="example[email1]" value="{$example->elements.email1->value}" />
					</label>

					<label{if $example->elements.email2->errors !== NULL} class="error"{/if}>
						<span>Repeated<sup>*</sup></span>
						<input type="text" name="example[email2]" value="{$example->elements.email2->value}" />
					</label>
				</fieldset>

				<fieldset class="buttons">
					<button type="submit">Submit form</button>
				</fieldset>
			</form>
		</section>
	</article>
{/block}