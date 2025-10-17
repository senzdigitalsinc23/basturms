<?php
namespace App\Core;

class EventDispatcher
{
    protected array $listeners = [];
    protected Queue $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, $payload = null): void
    {
        if (!empty($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {

                $result = $listener($payload);

                // If listener returns a Job that implements ShouldQueue, push to queue
                if ($result instanceof Job && $result instanceof ShouldQueue) {
                    $this->queue->push($result);
                }
            }
        }
    }
}
