<?php


namespace nyansapow\events;


class EventDispatcher
{
    private $listeners;

    public function addListener(string $event, callable $listener)
    {
        if(!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(object $event)
    {
        foreach($this->listeners[get_class($event)] ?? [] as $listener)
        {
            $listener($event);
        }
    }
}
