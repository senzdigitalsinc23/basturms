<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\TestCase;
use App\Core\Container;
use App\Core\EventDispatcher;
use App\Core\Queue;
use App\Jobs\SendWelcomeEmailJob;

class EventTest extends TestCase
{
    public function testEventDispatchQueuesJob()
    {
        $container = new Container();
        $queue = new Queue(__DIR__ . '/../storage/jobs_test');
        $dispatcher = new EventDispatcher($queue);

        $dispatcher->listen('user.registered', function ($user) {
            return new SendWelcomeEmailJob($user['email']);
        });

        $dispatcher->dispatch('user.registered', ['email' => 'test@example.com']);

        $files = glob(__DIR__ . '/../storage/jobs_test/*.job');
        $this->assertTrue(count($files) > 0, 'Job should be queued after event dispatch');

        // Clean up
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

$test = new EventTest();
$test->run();
