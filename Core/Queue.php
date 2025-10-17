<?php
namespace App\Core;

use App\Core\Container;

class Queue
{
    protected string $queueFile;

    public function __construct(string $queueFile = __DIR__ . '/../storage/queue.json')
    {
        $this->queueFile = $queueFile;

        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([]));
        }
    }

    public function push(object $job): void
    {
        $queue = json_decode(file_get_contents($this->queueFile), true);
        $queue[] = base64_encode(serialize($job));
        file_put_contents($this->queueFile, json_encode($queue));
    }

    public function process(): void
    {
        $queue = json_decode(file_get_contents($this->queueFile), true);

        while ($queue) {
            $jobData = array_shift($queue);
            $job = unserialize(base64_decode($jobData));

            if (method_exists($job, 'handle')) {
                $job->handle();
            }
        }

        file_put_contents($this->queueFile, json_encode($queue));
    }
}
