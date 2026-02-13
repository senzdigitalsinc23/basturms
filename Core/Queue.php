<?php

namespace App\Core;

class Queue
{
    /**
     * Dispatch a job to the database queue.
     * 
     * @param string $jobClass The Fully Qualified Class Name of the job.
     * @param array $data Data to be passed to the job.
     * @return void
     */
    public static function dispatch(string $jobClass, array $data): void
    {
        $db = Database::getInstance()->getConnection();
        $payload = json_encode($data);

        // Check if a pending job with the same class and payload already exists
        $checkSql = "SELECT id FROM queue_jobs WHERE job_class = :job_class AND payload = :payload AND status = 'pending' LIMIT 1";
        $stmt = $db->prepare($checkSql);
        $stmt->execute([
            ':job_class' => $jobClass,
            ':payload' => $payload
        ]);

        if ($stmt->fetch()) {
            // Job already queued and pending, skip insertion
            return;
        }
        
        $sql = "INSERT INTO queue_jobs (job_class, payload, status, created_at, updated_at) VALUES (:job_class, :payload, 'pending', NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':job_class' => $jobClass,
            ':payload' => $payload
        ]);
    }
}
