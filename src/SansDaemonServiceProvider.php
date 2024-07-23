<?php

declare(strict_types=1);

namespace Queueworker\SansDaemon;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Application as AppProp;
use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;
use Illuminate\Queue\QueueServiceProvider;
use Queueworker\SansDaemon\Console\WorkCommand;

/**
 * @property AppProp $app
 */
class SansDaemonServiceProvider extends QueueServiceProvider
{
    /**
     * List of supported base commands.
     *
     * @var array<string>
     */
    protected array $supportedBaseCommands = [
        BaseWorkCommand::class,
        'command.queue.work',
    ];

    /**
     * Register the application services.
     */
    public function register(): void
    {
        parent::register();

        $this->configureQueue();

        $this->registerWorkCommand();
    }

    /**
     * Configure the queue.
     *
     * @return void.
     */
    protected function configureQueue(): void
    {
        if ($this->app->bound('queue.sansDaemonWorker')) {
            return;
        }

        $this->app->singleton('queue.sansDaemonWorker', function () {
            $isDownForMaintenance = fn () => $this->app->isDownForMaintenance();

            return new SansDaemonWorker(
                $this->app['queue'],
                $this->app['events'],
                $this->app[ExceptionHandler::class],
                $isDownForMaintenance,
            );
        });
    }

    /**
     * Register the command.
     */
    protected function registerWorkCommand(): void
    {
        // We'll go through the list of known supported queue commands and
        // extend them if they've been bound to the container.
        foreach ($this->supportedBaseCommands as $baseCommand) {
            if ($this->app->bound($baseCommand)) {
                $this->app->extend(
                    $baseCommand,
                    static fn ($command, Application $app) => new WorkCommand(
                        $app['queue.sansDaemonWorker'], // @phpstan-ignore offsetAccess.nonOffsetAccessible
                        $app['cache.store'], // @phpstan-ignore offsetAccess.nonOffsetAccessible
                    ),
                );

                break;
            }
        }
    }
}
