<?php

declare(strict_types=1);

namespace Queueworker\SansDaemon;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

class SansDaemonWorker extends Worker
{
    /**
     * Get the next job from the queue connection.
     */
    public function getNextSansDaemonJob(Queue $connection, string $queue): ?Job
    {
        return $this->getNextJob($connection, $queue);
    }

    /**
     * Process the given job.
     */
    public function runSansDaemonJob(Job $job, string $connectionName, WorkerOptions $options): void
    {
        $this->runJob($job, $connectionName, $options);
    }
}
