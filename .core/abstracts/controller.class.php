<?php
namespace Glue\Abstracts;

/**
 * Abstract controller class
 *
 * @author Dirk Lüth <info@qoopido.de>
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
			$this->configuration = $this->factory->get('\Glue\Component\Configuration');
			$this->url           = $this->factory->get('\Glue\Component\Url');
			$this->request       = $this->factory->get('\Glue\Component\Request');
			$this->routing       = $this->factory->get('\Glue\Component\Routing');
			$this->client        = $this->factory->get('\Glue\Component\Client');
			$this->environment   = $this->factory->get('\Glue\Component\Environment');
			$this->header        = $this->factory->get('\Glue\Component\Header');
			$this->session       = $this->factory->get('\Glue\Component\Session');
			$this->view          = $this->factory->get('\Glue\Gateway\View');

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