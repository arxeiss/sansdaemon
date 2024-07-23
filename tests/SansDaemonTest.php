<?php

use Illuminate\Queue\WorkerOptions;
use Orchestra\Testbench\TestCase;

class SansDaemonTest extends TestCase
{
    protected $artisan;

    protected $worker;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');

        $this->worker = $this->app->make('queue.sansDaemonWorker');
    }

    public function testQueueWorkerCanRunInSansDaemonMode()
    {
        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function testQueueWorkerCanFireJob()
    {
        $job = new FakeWorkerJob;
        $this->worker->setManager($this->getManager('sync', ['default' => [$job]]));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        $this->assertTrue($job->fired);
    }

    public function testQueueWorkerCanExecutesNJobs()
    {
        $jobs = [
            'default' => [new FakeWorkerJob, new FakeWorkerJob, new FakeWorkerJob],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true, '--jobs' => 2,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerCanExecuteJobsInMultipleQueues()
    {
        $jobs = [
            'high' => [new FakeWorkerJob, new FakeWorkerJob],
            'default' => [new FakeWorkerJob],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true, '--jobs' => 3, '--queue' => 'high,default',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(0, $this->worker->getManager()->connection('sync')->size('high'));
        $this->assertEquals(0, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerExitsAfterMaxExecTime()
    {
        $jobs = [
            'default' => [new FakeWorkerJob(true, 10), new FakeWorkerJob(true)],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true, '--max_exec_time' => 5,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    public function testQueueWorkerDoesNotWaitForNextJobIfUnavailable()
    {
        $jobs = [
            'default' => [new FakeWorkerJob, (new FakeWorkerJob)->setNotAvailable()],
        ];

        $this->worker->setManager($this->getManager('sync', $jobs));

        $exitCode = $this->artisan->call('queue:work', [
            '--sansdaemon' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(1, $this->worker->getManager()->connection('sync')->size('default'));
    }

    //#####################
    // Helpers
    //#####################
    protected function getPackageProviders($app)
    {
        return ['Queueworker\SansDaemon\SansDaemonServiceProvider'];
    }

    private function getManager($connectionName, $jobs = [])
    {
        return new FakeWorkerManager($connectionName, new FakeWorkerConnection($jobs));
    }
}

//#####################
// Fakes
//#####################
class FakeWorkerManager extends \Illuminate\Queue\QueueManager
{
    public $connections = [];

    public function __construct($name, $connection)
    {
        $this->connections[$name] = $connection;
    }

    public function connection($name = null)
    {
        return $this->connections[$name];
    }
}

class FakeWorkerConnection
{
    public $jobs = [];

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function pop($queue)
    {
        [$availableJobs, $reservedJobs] = collect($this->jobs[$queue])->partition(function ($job) {
            return $job->available();
        });

        $nextJob = $availableJobs->shift();

        $this->jobs[$queue] = $availableJobs->merge($reservedJobs);

        return $nextJob;
    }

    public function size($queue)
    {
        return count($this->jobs[$queue]);
    }

    public function getConnectionName()
    {
        return "sync";
    }
}

class FakeWorkerJob extends \Illuminate\Queue\Jobs\Job implements \Illuminate\Contracts\Queue\Job
{
    public $fired = false;
    public $shouldSleep;
    public $sleepFor;
    public $isAvailable = true;

    public function __construct($shouldSleep = false, $sleepFor = 0)
    {
        $this->shouldSleep = $shouldSleep;
        $this->sleepFor = $sleepFor;
    }

    public function fire()
    {
        if ($this->shouldSleep) {
            sleep($this->sleepFor);
        }

        $this->fired = true;
    }

    public function attempts()
    {
        return 0;
    }

    public function failed($e)
    {
        $this->markAsFailed();
    }

    public function available()
    {
        return $this->isAvailable;
    }

    public function setNotAvailable()
    {
        $this->isAvailable = false;

        return $this;
    }

    public function getJobId()
    {
        return '';
    }

    public function getRawBody()
    {
        return '{}';
    }

    public function resolveName()
    {
        return 'FakeWorkerJob';
    }
}
