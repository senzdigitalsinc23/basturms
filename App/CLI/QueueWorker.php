<?php

namespace App\CLI;

use App\Core\Database;
use PDO;

class QueueWorker extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Process pending jobs from the queue.';

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle(array $args): void
    {
        $this->info("Starting queue worker...");
        $runOnce = in_array('--once', $args);
        $stopWhenEmpty = in_array('--stop-when-empty', $args);

        while (true) {
            $job = $this->getNextJob();

            if ($job) {
                $this->processJob($job);
                // sleep short time to prevent CPU hogging if looping fast
                usleep(100000); // 100ms
            } else {
                if ($runOnce || $stopWhenEmpty) {
                    $this->info("No jobs found. Exiting.");
                    break;
                }
                // No jobs, wait longer
                sleep(2);
            }

            if ($runOnce) {
                break;
            }
        }
    }

    private function getNextJob(): ?array
    {
        // Transaction should be better to lock row, but for now simple update
        // Using "Running" status to lock
        $stmt = $this->db->prepare("SELECT * FROM queue_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            // Mark as running
            $update = $this->db->prepare("UPDATE queue_jobs SET status = 'running', updated_at = NOW() WHERE id = ?");
            $update->execute([$job['id']]);
            return $job;
        }

        return null;
    }

    private function processJob(array $job): void
    {
        $this->info("Processing job ID: {$job['id']} ({$job['job_class']})");

        try {
            $class = $job['job_class'];
            $payload = json_decode($job['payload'], true);

            if (!class_exists($class)) {
                throw new \Exception("Job class $class not found");
            }

            $instance = new $class();
            if (method_exists($instance, 'handle')) {
                // Determine if handle accepts parameters or payload wrapper
                // For simplicity, we assume handle takes array payload or specific args
                // Let's rely on standard 'handle' method being called with named args from payload via reflection?
                // Or easier: pass payload to handle if it accepts distinct arguments
                
                // Inspect method
                $method = new \ReflectionMethod($instance, 'handle');
                $params = $method->getParameters();
                
                if (count($params) > 0) {
                     // Invoke with args matching payload keys
                     $args = [];
                     foreach ($params as $param) {
                         $name = $param->getName();
                         $args[] = $payload[$name] ?? null;
                     }
                     $method->invokeArgs($instance, $args);
                } else {
                    // No args?
                    $method->invoke($instance);
                }

            }

            // Mark completed
            $stmt = $this->db->prepare("UPDATE queue_jobs SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$job['id']]);
            $this->success("Job ID: {$job['id']} processed successfully.");

        } catch (\Exception $e) {
            $this->error("Job ID: {$job['id']} failed: " . $e->getMessage());
            // Mark failed
            $stmt = $this->db->prepare("UPDATE queue_jobs SET status = 'failed', error_message = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$e->getMessage(), $job['id']]);
        }
    }
}
