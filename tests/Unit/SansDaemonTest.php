<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\Job;
use Orchestra\Testbench\TestCase;
use Queueworker\SansDaemon\SansDaemonWorker;
use Tests\Helpers\FakeWorkerConnection;
use Tests\Helpers\FakeWorkerJob;
use Tests\Helpers\FakeWorkerManager;

class SansDaemonTest extends TestCase
{
    protected Kernel $artisan;

    protected SansDaemonWorker $worker;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        $this->worker = $this->app->make('queue.sansDaemonWorker');
    }

    public function testQueueWorkerCanRunInSansDaemonMode(): void
    {
        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function testQueueWorkerCanFireJob(): void
    {
        $job = new FakeWorkerJob();
        $this->worker->setManager($this->getManager('sync', ['default' => [$job]]));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $this->assertTrue($job->fired);
    }

    public function testQueueWorkerCanExecutesNJobs(): void
    {
        $jobs = [
            'default' => [new FakeWorkerJob(), new FakeWorkerJob(), new FakeWorkerJob()],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
            '--jobs' => 2,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerCanExecuteJobsInMultipleQueues(): void
    {
        $jobs = [
            'high' => [new FakeWorkerJob(), new FakeWorkerJob()],
            'default' => [new FakeWorkerJob()],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
            '--jobs' => 3,
            '--queue' => 'high,default',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(0, $this->worker->getManager()->connection('sync')->size('high'));
        $this->assertEquals(0, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerExitsAfterMaxExecTime(): void
    {
        $jobs = [
            'default' => [new FakeWorkerJob(true, 1), new FakeWorkerJob(true, 3), new FakeWorkerJob()],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
            '--max_exec_time' => 3,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerDoesNotWaitForNextJobIfUnavailable(): void
    {
        $jobs = [
            'default' => [new FakeWorkerJob(), (new FakeWorkerJob())->setNotAvailable()],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    /**
     * Helper for Testbench
     *
     * @param mixed $app
     *
     * @return array<string>
     */
    protected function getPackageProviders($app): array
    {
        return ['Queueworker\SansDaemon\SansDaemonServiceProvider'];
    }

    /**
     * @param array<string, array<Job>> $jobs
     */
    private function getManager(string $connectionName, array $jobs = []): FakeWorkerManager
    {
        return new FakeWorkerManager($connectionName, new FakeWorkerConnection($jobs));
    }
}
