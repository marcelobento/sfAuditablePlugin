<?php
class sfAuditableListener extends Doctrine_Record_Listener 
{
	protected $_options;
	
  public function __construct(array $options = array())
  {
    $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
  }

	public function postInsert(Doctrine_Event $event)
	{
		if ($this->_options["create"]["track"])
		{
			$this->logEvent($event->getInvoker(), $this->_options["create"]);
		}
	}
	
	public function preUpdate(Doctrine_Event $event)
	{
		if ($this->_options["update"]["track"])
		{
			$this->logEvent($event->getInvoker(), $this->_options["update"]);
		}
	}
	
	public function preDelete(Doctrine_Event $event)
	{
		if ($this->_options["delete"]["track"])
		{
			$this->logEvent($event->getInvoker(), $this->_options["delete"]);
		}
	}
	
	protected function logEvent($object, $options)
	{
		// If there is no context, then the event is triggered by the CLI task maybe
		if (!sfContext::hasInstance())
		{
			return;
		}
		$context = sfContext::getInstance();
		$user = $context->getUser();
		$logItem = new sfAuditableLogItem();
		$logItem->setIssuer($user->getGuardUser());
		if ($object != null && is_callable(array($object, "getId")));
		{
			$objectId = $object->getId();
			$objectClass = get_class($object);
			$logItem->setObjectId($objectId);
			$logItem->setObjectClass($objectClass);
			$name = "";
			if (isset($this->_options["nameField"]))
			{
				$name = $object->get($this->_options["nameField"]);
			}
			$message = $options["message"];
			sfContext::getInstance()->getConfiguration()->loadHelpers("Url");
			if (isset($options["link"]))
			{
				$link = str_replace("%ID%", $objectId, $options["link"]);
				$objectWithLink = '<a href="' . url_for($link) . '">' . $this->_options["name"] . '</a>';
			} else {
				$objectWithLink = $this->_options["name"];
			}
			$object = $this->_options["name"];
			$message = str_replace(
				array("%OBJECT_WITH_LINK%", "%OBJECT%", "%NAME%"),
				array($objectWithLink, $object, $name),
				$message
			);
			$logItem->setText($message);
		}
		$logItem->save();
	}
}