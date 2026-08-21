<?php

namespace foonoo\tests\Unit\Events;

use foonoo\events\EventDispatcher;
use foonoo\exceptions\FoonooException;
use foonoo\tests\BaseTestCase;

class EventDispatcherTest extends BaseTestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }

    public function testRegisterAndDispatchEvent(): void
    {
        $this->dispatcher->registerEventType('user.created', function (array $args) {
            return (object)[
                'name' => $args['name'],
                'handled' => false
            ];
        });

        $eventReceived = null;
        $this->dispatcher->addListener('user.created', function ($event) use (&$eventReceived) {
            $event->handled = true;
            $eventReceived = $event;
        });

        $dispatched = $this->dispatcher->dispatch('user.created', ['name' => 'Alice']);

        $this->assertNotNull($eventReceived);
        $this->assertSame('Alice', $eventReceived->name);
        $this->assertTrue($eventReceived->handled);
        $this->assertSame($eventReceived, $dispatched);
    }

    public function testDispatchWithoutListenersReturnsNull(): void
    {
        $this->dispatcher->registerEventType('empty.event', fn($args) => $args);
        $result = $this->dispatcher->dispatch('empty.event', ['test' => 1]);
        $this->assertNull($result);
    }

    public function testRemoveListener(): void
    {
        $this->dispatcher->registerEventType('item.updated', fn($args) => $args);

        $calls = 0;
        $id = $this->dispatcher->addListener('item.updated', function () use (&$calls) {
            $calls++;
        });

        $this->dispatcher->dispatch('item.updated', []);
        $this->assertSame(1, $calls);

        $this->dispatcher->removeListener('item.updated', $id);
        $this->dispatcher->dispatch('item.updated', []);
        $this->assertSame(1, $calls);
    }

    public function testAddListenerUnregisteredEventThrowsException(): void
    {
        $this->expectException(FoonooException::class);
        $this->expectExceptionMessage("Event type nonexistent.event has not been registered with the event dispatcher");

        $this->dispatcher->addListener('nonexistent.event', function () {});
    }

    public function testDispatchUnregisteredEventThrowsException(): void
    {
        $this->expectException(FoonooException::class);
        $this->expectExceptionMessage("Event type nonexistent.event has not been registered with the event dispatcher");

        $this->dispatcher->dispatch('nonexistent.event', []);
    }
}
