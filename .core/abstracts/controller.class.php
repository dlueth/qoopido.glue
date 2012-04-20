<?php
namespace Glue\Abstracts;

/**
 * Abstract controller class
 *
 * @author Dirk Lüth <dirk@qoopido.de>
 */
abstract class Controller extends \Glue\Abstracts\Base {
	/**
	 * Property to provide event dispatcher
	 */
	protected $dispatcher;

	/**
	 * Property to provide factory
	 */
	protected $factory;

	/**
	 * Property to provide configuration
	 */
	protected $configuration;

	/**
	 * Property to provide request
	 */
	protected $request;

	/**
	 * Property to provide client
	 */
	protected $client;

	/**
	 * Property to provide environment
	 */
	protected $environment;

	/**
	 * Property to provide header
	 */
	protected $header;

	/**
	 * Property to provide session
	 */
	protected $session;

	/**
	 * Property to provide view
	 */
	protected $view;

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final public function __construct() {
		try {
			$arguments = func_get_args();

			$this->dispatcher    = \Glue\Dispatcher::getInstance();
			$this->factory       = \Glue\Factory::getInstance();
			$this->configuration = $this->factory->get('\Glue\Components\Configuration');
			$this->url           = $this->factory->get('\Glue\Components\Url');
			$this->request       = $this->factory->get('\Glue\Components\Request');
			$this->client        = $this->factory->get('\Glue\Components\Client');
			$this->environment   = $this->factory->get('\Glue\Components\Environment');
			$this->header        = $this->factory->get('\Glue\Components\Header');
			$this->session       = $this->factory->get('\Glue\Components\Session');
			$this->view          = $this->factory->get('\Glue\Adapter\View');

			if(count($arguments) > 0) {
				call_user_func_array(array('parent', '__construct'), $arguments);
			} else {
				parent::__construct();
			}

			unset($arguments);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}
}
?>