{extends file='_syntaxhighlight.tpl'}

{block name=content}
	<article>
		<hgroup>
			<h6>Enhancing performance</h6>
			<h1>Caching complex operations</h1>
		</hgroup>

		<p>
			Visitors always want websites to be fast and responsive. Same applies to developers as well as server administrators, though for different reasons. No matter from what point you look at it caching might, in some situations where code itself cannot be optimized further, come in handy and be a benefit for everyone involved.
		</p>

		<p>
			One major concern about caching is exactly when to invalidate a certain cache. The most simple approach is to give every cache a fixed lifetime. No matter what happens the cache will be served as long as it is not expired - and you might for that time deliver old or even wrong/malicious content to your visitors. The only way to influence this kind of cache would be to flush it manually, in case you know exactly which cache is involved.
		</p>

		<p>
			Qoopido Glue comes with built-in cache entities which try to avoid such drawbacks and offer other means instead. There currently are cache entities for filesystem and APC caches which are totally interchangeable from the developers point of view. The framework components for configuration and server use either APC or filesystem caches to store there processed information. Language and tree module do the same for their processed files.
		</p>

		<section>
			<h5>Controller code</h5>
			<p>
				To give you a better example of how versatile built-in caches are here is an example mainly taken from Qoopido Glue's language module adjusted to be usable in any controller:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
			...

			// define some language files to be loaded
			$files = array(
				$this->environment->get('path.global) . '/.internationalization/language/.default.xml',
				$this->environment->get('path.local) . '/.internationalization/language/.default.xml'
			);

			// define the cache id/path
			$id = $this->environment->get('path.local) . '/.cache/' . __CLASS__ . '/' . sha1(serialize($files));

			// initialize cache and set dependencies to local files
			$cache = \Glue\Entity\Cache\File::getInstance($id)
				->setDependencies($files);

			// try to fetch content of cache
			if(($data = $cache->get()) === false) {
				// load files if cache does not exist or is invalid
				if(($data = \Glue\Helper\General::loadConfiguration($cache->dependencies)) !== false) {
					// further process loaded files
					foreach($data as $k => $v) {

					}

					// store processed data in cache
					$cache->setData($data)->set();
				}
			}

			...
			]]></script>

			<p>
				As you might have seen the only thing invalidating this cache is a modification of its depending files - ideal if only file operations are involved. But there are more possibilities to invalidate a cache. And the best: They can all be combined! Let us say we wanted to additionally depend the cache from the prior example on the server's document root because it also stores some critical absolute path information:
			</p>

			<script type="syntaxhighlighter" class="brush: php"><![CDATA[
			...

			// take the last example...
			$cache = \Glue\Entity\Cache\File::getInstance($id)
				->setDependencies($files);

			// ... and change it to
			$cache = \Glue\Entity\Cache\File::getInstance($id)
				->setDependencies($files)
				->setComparator(sha1($_SERVER['DOCUMENT_ROOT']));

			// ... and for those missing a lifetime
			$cache = \Glue\Entity\Cache\File::getInstance($id)
				->setLifetime(strtotime('+1 hour'))
				->setDependencies($files)
				->setComparator(sha1($_SERVER['DOCUMENT_ROOT']));

			// ... and even more funky stuff
			$cache = \Glue\Entity\Cache\File::getInstance($id)
				->setReload(true)
				->setLifetime(strtotime('+1 hour'))
				->setDependencies($files)
				->setComparator(sha1($_SERVER['DOCUMENT_ROOT']));
			...
			]]></script>

			<p>
				The last example shows one of the special features of Qoopido Glue as it also focuses on correct handling of request and response headers and offers ways to behave accordingly. "reload" is a flag stored in the client component which represents if the user did a forced/hard or a soft reload. Cache entities can be enabled to react on this flag invalidating themselves when a user forces a hard reload.
			</p>
		</section>
	</article>
{/block}