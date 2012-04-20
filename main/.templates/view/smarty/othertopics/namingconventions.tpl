{extends file='_main.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Namespaces finally arrived</h6>
			<h1>Consistency throughout the framework</h1>
		</hgroup>

		<p>
			PHP 5.3 finally brought namespace support into the game and Qoopido Glue makes extensive use of this new and highly desired feature. Namespaces are not only used to avoid class conflicts but are deeply integrated into the system. The combination of namespace and classname is directly mapped to a "path" for all core classes of Qoopido Glue and is used in several locations:
		</p>

		<ol>
			<li>Filesystem path for the autoloader</li>
			<li>Class-Identifier in Factory</li>
			<li>Class-ID for all classes</li>
			<li>XML-Structure and location in configuration files</li>
			<li>Filesystem path for caches</li>
			<li>Page nodename/alias in URLs, events, exceptions</li>
			<li>Path and classname for controllers and templates</li>
		</ol>

		<p>
			The result is exceptionally versatile and only has an absolute minimum of drawbacks. For the "path" is used almost everywhere it has to be sluggified at some point. As a result page nodenames may e.g. consist of letters, numbers and dashes. Although dashes are still allowed in directory- and filenames they are not allowed in namespaces or classnames. Therefore Qoopido Glue sluggifies both into a dashless representation and uses it to find controllers and templates. Just to give you a quick example:
		</p>

		<p>
			The nodename of the current page is "{$environment.node}" (alias is "{$environment.alias}") which is mapped to the template "{$environment.slug}.tpl" in the main template directory. The controller that would be looked for would have been "\Glue\Controller\Othertopics\Namingconventions" which would have been mapped to "main/.controller/othertopics/namingconventions.class.php" by the autoloader.
		</p>
	</article>
{/block}