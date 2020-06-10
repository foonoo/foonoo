<?php


namespace foonoo\events;


use foonoo\NyansapowException;

class EventDispatcher
{
    private $listeners;
    private $eventTypes;
    private $activeSite;

    public function addListener(string $eventType, callable $listener) : void
    {
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }
        $this->listeners[$eventType][] = $listener;
    }

    public function dispatch(string $eventType, array $args)
    {
        if(empty($this->listeners[$eventType])) {
            return null;
        }
        $event = $this->createEvent($eventType, $args);
        foreach ($this->listeners[$eventType] ?? [] as $listener) {
            $listener($event);
        }
        return $event;
    }

    private function createEvent(string $event, array $args)
    {
        if(!isset($this->eventTypes[$event])) {
            throw new NyansapowException("Event type [{$event}] has not been registered.");
        }
        return $this->eventTypes[$event]($args);
    }

    public function registerEventType(string $eventType, callable $factory) : void
    {
        $this->eventTypes[$eventType] = $factory;
    }

    public function setActiveSite($site) : void
    {
        $this->activeSite = $site;
    }
}
