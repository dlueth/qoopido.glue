<?php
namespace Glue\Controller\Usecases;

class Events extends \Glue\Controller\General {
	private $result = 12;
	private $delta  = 0;
	private $events = array();

	protected function __initialize() {
		// call parent constructor
		parent::__initialize();

		// bind method onPreRender to the system event glue.gateway.view.render.pre
		$this->dispatcher->addListener(array(&$this, 'onPreRender'), 'glue.gateway.view.render.pre');

		// bind method onMath to all custom "math" events
		$this->dispatcher->addListener(array(&$this, 'onMath'), 'math');

		// bind method onDeltaChange to the custom "math.add" and "math.subtract" events
		$this->dispatcher->addListener(array(&$this, 'onDeltaChange'), array('math.add', 'math.subtract'));

		// bind method onMathAdd to the custom "math.add" event
		$this->dispatcher->addListener(array(&$this, 'onMathAdd'), 'math.add');

		// bind method onMathSubtract to the custom "math.subtract" event
		$this->dispatcher->addListener(array(&$this, 'onMathSubtract'), 'math.subtract');

		// trigger custom event a couple of times
		$this->dispatcher->notify(new \Glue\Event('math.add', array(13)));
		$this->dispatcher->notify(new \Glue\Event('math.add', array(11)));
		$this->dispatcher->notify(new \Glue\Event('math.subtract', array(8)));
		$this->dispatcher->notify(new \Glue\Event('math.add', array(14)));
	}

	public function onMath($event) {
		$this->events[] = $event->name;
	}

	public function onDeltaChange($event, $value) {
		$value = (int) $value;

		switch($event->name) {
			case 'math.subtract':
				$value = -$value;
				break;
		}

		$this->delta += $value;
	}

	public function onMathAdd($event, $value) {
		$this->result += (int) $value;
	}

	public function onMathSubtract($event, $value) {
		$this->result -= (int) $value;
	}

	public function onPreRender() {
		// assign values to view just before it gets rendered
		$this->view->register('events', $this->events);
		$this->view->register('delta', $this->delta);
		$this->view->register('result', $this->result);
	}
}
?>