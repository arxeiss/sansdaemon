<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueManager;

class FakeWorkerManager extends QueueManager
{
    public function __construct(string $name, Queue $connection)
    {
        $this->connections[$name] = $connection;
    }

    /**
     * @param string|null $name
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function connection($name = null): ?Queue
    {
        return $this->connections[$name] ?? null;
    }
}
