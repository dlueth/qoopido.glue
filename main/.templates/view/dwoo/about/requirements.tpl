{extends file='_main.tpl'}

{block name=aside}
	<aside class="sidebar">
		<h6>Footnotes</h6>
	
		<dl>
			<dt><sup>1</sup></dt>
				<dd>PHP extensions not enabled or installed by default</dd>
			<dt><sup>2</sup></dt>
				<dd>Version of gd should be greater 2.0.3</dd>
			<dt><sup>3</sup></dt>
				<dd>PHP's PDO extension itself is installed and enabled by default but drivers beside SQLite are not</dd>
		</dl>
	</aside>
{/block}

{block name=content}
	<article>
		<hgroup>
			<h6>Modern architecture has modern requirements</h6>
			<h1>Things you should know about your server</h1>
		</hgroup>

		<p>
			Qoopido Glue should run on any Linux Server running Apache 2 and at least PHP 5.3 or upwards. Regarding PHP some configuration will most likely be necessary as Qoopido Glue needs write permissions on cache- and logfile-directories.
		</p>

		<h5>Core requirements</h5>
		<ul>
			<li>Apache version 2+
				<ul>
					<li>support for .htaccess files</li>
				</ul>
			</li>
			<li>PHP version 5.3+
				<ul>
					<li>write permission .cache, cache &amp; .logfiles directories</li>
					<li>Extension: mbstring <sup>1</sup></li>
					<li>Extension: libxml</li>
					<li>Extension: simplexml</li>
				</ul>
			</li>
		</ul>

		<h5>Optional requirements</h5>
		<ul>
			<li>Apache modules: mod_rewrite, mod_deflate</li>
			<li>PHP extensions: apc <sup>1</sup>, curl <sup>1</sup>, fileinfo, gd <sup>1, 2</sup>, iconv, mcrypt <sup>1</sup>, pdo <sup>3</sup>, session, zlib, exif</li>
		</ul>

		<h5>Additional recommendations</h5>
		<ul>
			<li>Check PHP.ini directives:
				<ul>
					<li>date_default_timezone: adjust to your projects' needs</li>
					<li>file_uploads: adjust to your projects' needs</li>
					<li>max_execution_time: adjust to your projects' needs</li>
					<li>max_file_uploads: adjust to your projects' needs</li>
					<li>max_input_time: adjust to your projects' needs</li>
					<li>memory_limit: adjust to your projects' needs</li>
					<li>post_max_size: adjust to your projects' needs</li>
					<li>register_globals: should ALWAYS be turned off</li>
					<li>upload_max_filesize: adjust to your projects' needs</li>
				</ul>
			</li>
		</ul>
	</article>
{/block}