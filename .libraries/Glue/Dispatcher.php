<?php
namespace Glue;

/**
 * Centralized event dispatcher
 *
 * @require PHP "SIMPLEXML" extension
 *
 * @author Dirk Lüth <info@qoopido.com>
 */
final class Dispatcher extends \Glue\Abstracts\Base\Singleton {
	/**
	 * Private property to store event callbacks
	 *
	 * @array
	 */
	private $callbacks = array();

	/**
	 * Private property to store events and listeners
	 *
	 * @object \SimpleXMLElement
	 */
	private $events;

	/**
	 * Static, once only constructor
	 *
	 * @throw \LogicException
	 */
	public static function __once() {
		if(extension_loaded('simplexml') !== true) {
			throw new \LogicException(\Glue\Helper\General::replace(array('class' => __CLASS__, 'extension' => 'SIMPLEXML'), GLUE_EXCEPTION_EXTENSION_MISSING));
		}
	}

	/**
	 * Class constructor
	 *
	 * @throw \RuntimeException
	 */
	final public function __initialize() {
		try {
			$this->events = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><events></events>');
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('class' => __CLASS__), GLUE_EXCEPTION_CLASS_INITIALIZE), NULL, $exception);
		}
	}

	/**
	 * Registers a listener passed as a callback to an event
	 *
	 * @param object $callback
	 * @param string $events
	 * @param bool $once
	 *
	 * @return bool
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final public function addListener($callback, $events, $once = false) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$callback' => array($callback, 'isCallback'),
			'@$events'  => array($events, 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try{
			$events = (array) $events;
			$id     = (is_array($callback)) ? sha1(get_class($callback[0]) . json_encode($callback)) : sha1(json_encode($callback));

			foreach($events as $event) {
				$matches = $this->_getEvent($this->_sanitizeEventName($event));

				foreach($matches as $match) {
					$status = $match->xpath('./listener[@id=\'' . $id . '\']');
					$status = ($status === false || count($status) === 0) ? false : true;

					if($status === false) {
						$listener         = $match->addChild('listener');
						$listener['id']   = $id;
						$listener['once'] = $once;
					}
				}

				if(!isset($this->callbacks[$id])) {
					$this->callbacks[$id] =& $callback;
				}
			}

			unset($callback, $events, $result, $id, $event, $matches, $match, $status, $listener);

			return true;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Notifies all listeners for a specific event
	 *
	 * @param object $event
	 *
	 * @return bool
	 *
	 * @throw \RuntimeException
	 */
	final public function notify(\Glue\Event &$event) {
		try{
			$return = false;

			if($this->events !== NULL) {
				$name = $this->_sanitizeEventName($event->name);

				if($this->_getEvent($name, false) === false) {
					$this->_buildEvent($name);
				}

				$expression =  '/events/' . $name . '[not(@id)]/listener[@id]|/events/' . $name . '[not(@id)]/ancestor::*/listener[@id]';
				$matches    =  $this->events->xpath($expression);

				if(is_array($matches)) {
					$parameters = $event->parameters;
					array_unshift($parameters, $event);

					foreach($matches as $match) {
						if($match['once'] == true) {
							$match->parentNode->removeChild($match);
						}

						if(call_user_func_array($this->callbacks[(string) $match['id']], $parameters) === false) {
							break;
						}
					}
				}

				$return = true;

				unset($name, $expression, $matches, $parameters, $match);
			}

			unset($event);

			return $return;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Removes illegal characters from a passed event name
	 *
	 * @param  $name
	 *
	 * @return string
	 *
	 * @throw \InvalidArgumentException
	 * @throw \RuntimeException
	 */
	final private function _sanitizeEventName($name) {
		if(($result = \Glue\Helper\Validator::batch(array(
			'$name' => array($name, 'isString', 'isNotEmpty')
		))) !== true) {
			throw new \InvalidArgumentException(\Glue\Helper\General::replace(array('method' => __METHOD__, 'parameter' => $result), GLUE_EXCEPTION_PARAMETER));
		}

		try {
			unset($result);

			return preg_replace('/(?:^\/)|(?:\/$)/', '', strtolower(preg_replace('/[.\\\\\/]+/', '/', $name)));
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Returns a pointer to the passed event
	 *
	 * @param string $node
	 * @param bool $autocreate
	 *
	 * @return array
	 *
	 * @throw \RuntimeException
	 */
	final private function _getEvent($node, $autocreate = true) {
		try {
			$matches = $this->events->xpath('/events/' . $node . '[not(@id)]');

			if($autocreate === true && ($matches === false || count($matches) === 0)) {
				$matches = $this->_buildEvent($node);
			}

			unset($node, $autocreate);

			return (count($matches) > 0 ) ? $matches : false;
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}

	/**
	 * Builds an event tree for a passed event
	 *
	 * @param string $node
	 *
	 * @return array
	 *
	 * @throw \RuntimeException
	 */
	final private function _buildEvent($node) {
		try {
			$nodes   =  explode('/', $node);
			$pointer =& $this->events;

			foreach($nodes as $node) {
				if(!isset($pointer->$node)) {
					$pointer->addChild($node);
				}

				$pointer =& $pointer->$node;
			}

			unset($node, $nodes);

			return array($pointer);
		} catch(\Exception $exception) {
			throw new \RuntimeException(\Glue\Helper\General::replace(array('method' => __METHOD__), GLUE_EXCEPTION_METHOD_FAILED), NULL, $exception);
		}
	}
}
