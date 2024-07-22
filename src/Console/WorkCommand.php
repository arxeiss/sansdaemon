<?php

namespace Queueworker\SansDaemon\Console;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;
use Illuminate\Queue\Worker;
use Queueworker\SansDaemon\SansDaemonWorker;

/**
 * @property SansDaemonWorker $worker should contain only own worker if everything is correctly configured.
 */
class WorkCommand extends BaseWorkCommand
{
    /** @var $worker SansDaemonWorker */

    /**
     * Number of jobs processed.
     *
     * @var int
     */
    protected $jobsProcessed = 0;

    /**
     * Create a new queue work command.
     *
     * @return void
     */
    public function __construct(Worker $worker, Cache $cache)
    {
        // This is by default in Laravel, but in public/index.php so to be sure, let's define if missing.
        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        // Get default max execution time - 5s
        $maxExecutionTime = (int)ini_get('max_execution_time');
        $maxExecutionTime = $maxExecutionTime <= 0 ? 0 : $maxExecutionTime - 5;

        $this->signature .= '{--sansdaemon : Run the worker without a daemon}
                             {--jobs=0 : Number of jobs to process before worker exits}
                             {--max_exec_time='.$maxExecutionTime.' : Maximum seconds to run to prevent error (0 - forever)}';

        $this->description .= ' or sans-daemon';

        parent::__construct($worker, $cache);
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return array
     */
    protected function runWorker($connection, $queue)
    {
        if ($this->option('sansdaemon')) {
            $this->worker->setCache($this->cache);

            return $this->runSansDaemon($connection, $queue);
        }

        return parent::runWorker($connection, $queue);
    }

    /**
     * Process the queue sans-daemon mode.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return void
     */
    protected function runSansDaemon($connection, $queue)
    {
        while ($this->shouldRunNextJob() && !is_null($job = $this->getNextJob($connection, $queue))) {
            $this->worker->runSansDaemonJob($job, $connection, parent::gatherWorkerOptions());

            $this->jobsProcessed += 1;
        }
    }

    /**
     * Determine if the next job should be processed.
     *
     * @return bool
     */
    protected function shouldRunNextJob()
    {
        if ($this->isOverMaxExecutionTime()) {
            return false;
        }

        $maxJobsToRun = (int) $this->option('jobs');
        if ($maxJobsToRun <= 0) {
            return true;
        }

        return $this->jobsProcessed < $maxJobsToRun;
    }

    /**
     * Detect if the worker is running longer than the maximum execution time.
     *
     * @return bool
     */
    protected function isOverMaxExecutionTime()
    {
        $max_exec_time = (int) $this->option('max_exec_time');

        if ($max_exec_time <= 0) {
            return false;
        }

        $elapsedTime = microtime(true) - LARAVEL_START;

        return $elapsedTime > $max_exec_time;
    }

    /**
     * Get the next available job from the given queue.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        return $this->worker->getNextSansDaemonJob(
            $this->worker->getManager()->connection($connection),
            $queue,
        );
    }
}
