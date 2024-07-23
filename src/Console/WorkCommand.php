<?php

declare(strict_types=1);

namespace Queueworker\SansDaemon\Console;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;
use Illuminate\Queue\Worker;
use Queueworker\SansDaemon\SansDaemonWorker;

/**
 * @property SansDaemonWorker $worker should contain only own worker if everything is correctly configured.
 */
class WorkCommand extends BaseWorkCommand
{
    protected int $jobsProcessed = 0;

    public function __construct(Worker $worker, Cache $cache)
    {
        // This is by default in Laravel, but in public/index.php so to be sure, let's define if missing.
        if (!\defined('LARAVEL_START')) {
            \define('LARAVEL_START', \microtime(true));
        }

        // Get default max execution time - 5s
        $maxExecutionTime = (int)\ini_get('max_execution_time');
        $maxExecutionTime = $maxExecutionTime <= 0 ? 0 : $maxExecutionTime - 5;

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->signature .= '{--sansdaemon : Run the worker without a daemon}
                             {--jobs=0 : Number of jobs to process before worker exits}
                             {--max_exec_time=' . $maxExecutionTime . ' : Maximum seconds to run to prevent error (0 - forever)}';
        // phpcs:enable

        $this->description .= ' or sans-daemon';

        parent::__construct($worker, $cache);
    }

    /**
     * @param  string $connection
     * @param  string $queue
     * @return int|null
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
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
     */
    protected function runSansDaemon(string $connection, string $queue): int
    {
        // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
        while ($this->shouldRunNextJob() && ($job = $this->getNextJob($connection, $queue)) !== null) {
            $this->worker->runSansDaemonJob($job, $connection, parent::gatherWorkerOptions());

            $this->jobsProcessed += 1;
        }

        return 0;
    }

    /**
     * Determine if the next job should be processed.
     */
    protected function shouldRunNextJob(): bool
    {
        if ($this->isOverMaxExecutionTime()) {
            return false;
        }

        $maxJobsToRun = (int)$this->option('jobs');
        if ($maxJobsToRun <= 0) {
            return true;
        }

        return $this->jobsProcessed < $maxJobsToRun;
    }

    /**
     * Detect if the worker is running longer than the maximum execution time.
     */
    protected function isOverMaxExecutionTime(): bool
    {
        $max_exec_time = (int)$this->option('max_exec_time');

        if ($max_exec_time <= 0) {
            return false;
        }

        // @phpstan-ignore constant.notFound
        $elapsedTime = \microtime(true) - \LARAVEL_START;

        return $elapsedTime > $max_exec_time;
    }

    /**
     * Get the next available job from the given queue.
     */
    protected function getNextJob(string $connection, string $queue): ?Job
    {
        return $this->worker->getNextSansDaemonJob(
            $this->worker->getManager()->connection($connection),
            $queue,
        );
    }
}
