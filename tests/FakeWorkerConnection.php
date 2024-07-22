<?php

declare(strict_types=1);

namespace Tests;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;

// phpcs:disable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
class FakeWorkerConnection implements Queue
{
    /** @var array<string, array<Job>> */
    public array $jobs = [];

    /**
     * @param array<string, array<Job>> $jobs
     */
    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * @param string|null $queue
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function pop($queue = null): ?Job
    {
        [$availableJobs, $reservedJobs] = \collect($this->jobs[$queue])->partition(
            static fn ($job) => $job->available(),
        );

        $nextJob = $availableJobs->shift();

        $this->jobs[$queue] = $availableJobs->merge($reservedJobs);

        return $nextJob;
    }

    /**
     * @param string|null $queue
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function size($queue = null): int
    {
        return \count($this->jobs[$queue]);
    }

    public function getConnectionName(): string
    {
        return 'sync';
    }

    /**
     * @param  string|object $job
     * @param  mixed         $data
     * @param  string|null   $queue
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function push($job, $data = '', $queue = null)
    {
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string        $queue
     * @param  string|object $job
     * @param  mixed         $data
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function pushOn($queue, $job, $data = '')
    {
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string       $payload
     * @param  string|null  $queue
     * @param  array<mixed> $options
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function pushRaw($payload, $queue = null, $options = [])
    {
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  DateTimeInterface|DateInterval|int $delay
     * @param  string|object                      $job
     * @param  mixed                              $data
     * @param  string|null                        $queue
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
    }

    /**
     * Push a new job onto a specific queue after (n) seconds.
     *
     * @param  string                             $queue
     * @param  DateTimeInterface|DateInterval|int $delay
     * @param  string|object                      $job
     * @param  mixed                              $data
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array<mixed> $jobs
     * @param  mixed        $data
     * @param  string|null  $queue
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
    }

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function setConnectionName($name): self
    {
        return $this;
    }
}
