<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueManager;

class FakeWorkerManager extends QueueManager
{
    public function __construct(Application $app, string $name, Queue $connection)
    {
        parent::__construct($app);

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
