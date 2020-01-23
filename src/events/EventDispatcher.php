<?php


namespace nyansapow\events;


use nyansapow\NyansapowException;

class EventDispatcher
{
    private $listeners;
    private $eventTypes;

    public function addListener(string $eventType, callable $listener)
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
            throw new NyansapowException("Event type [{$event}] does not exist");
        }
        return $this->eventTypes[$event]($args);
    }

    public function registerEventType(string $eventType, callable $factory)
    {
        $this->eventTypes[$eventType] = $factory;
    }
}
