<?php


namespace foonoo\events;


use foonoo\FoonooException;

class EventDispatcher
{
    private $listeners = [];
    private $eventTypes = [];

    public function addListener(string $eventType, callable $listener) : void
    {
        $this->checkEventType($eventType);
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }
        $this->listeners[$eventType][] = $listener;
    }

    public function dispatch(string $eventType, array $args)
    {
        $this->checkEventType($eventType);
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
            throw new FoonooException("Event type [{$event}] has not been registered.");
        }
        return $this->eventTypes[$event]($args);
    }

    public function registerEventType(string $eventType, callable $factory) : void
    {
        $this->eventTypes[$eventType] = $factory;
    }

    private function checkEventType(string $eventType)
    {
        if(!array_key_exists($eventType, $this->eventTypes)) {
            throw new FoonooException("Event type {$eventType} has not been registered with the event dispatcher");
        }
    }
}
