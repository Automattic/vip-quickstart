<?php
/**
 * Kohana event subject. Uses the SPL observer pattern.
 *
 * @author     Kohana Team, Corey Worrell
 * @copyright  (c) 2007-2008 Kohana Team
 * @version    1.0
 * @package    eventbrite-parent
 */
abstract class Event_Subject {

	// Attached subject listeners
	protected $listeners = array();

	/**
	 * Attach an observer to the object.
	 *
	 * @chainable
	 * @param   object  Event_Observer
	 * @return  object
	 */
	public function attach(Event_Observer $obj)
	{
		// Add a new listener
		$this->listeners[spl_object_hash($obj)] = $obj;

		return $this;
	}

	/**
	 * Detach an observer from the object.
	 *
	 * @chainable
	 * @param   object  Event_Observer
	 * @return  object
	 */
	public function detach(Event_Observer $obj)
	{
		// Remove the listener
		unset($this->listeners[spl_object_hash($obj)]);

		return $this;
	}

	/**
	 * Notify all attached observers of a new message.
	 *
	 * @chainable
	 * @param   mixed   message string, object, or array
	 * @return  object
	 */
	public function notify($message)
	{
		foreach ($this->listeners as $obj)
		{
			$obj->notify($message);
		}

		return $this;
	}

}