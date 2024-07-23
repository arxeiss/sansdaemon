<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

class FakeWorkerJob extends Job implements JobContract
{
    public bool $fired = false;
    public bool $shouldSleep;
    public int $sleepFor;
    public bool $isAvailable = true;

    public function __construct(bool $shouldSleep = false, int $sleepFor = 0)
    {
        $this->shouldSleep = $shouldSleep;
        $this->sleepFor = $sleepFor;
    }

    public function fire(): void
    {
        if ($this->shouldSleep) {
            \sleep($this->sleepFor);
        }

        $this->fired = true;
    }

    public function attempts(): int
    {
        return 0;
    }

    /**
     * @param \Throwable|null $e
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function failed($e): void
    {
        $this->markAsFailed();
    }

    public function available(): bool
    {
        return $this->isAvailable;
    }

    public function setNotAvailable(): self
    {
        $this->isAvailable = false;

        return $this;
    }

    public function getJobId(): string
    {
        return '';
    }

    public function getRawBody(): string
    {
        return '{}';
    }

    public function resolveName(): string
    {
        return 'FakeWorkerJob';
    }
}
