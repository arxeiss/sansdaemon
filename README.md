# SansDaemon

> This is fork of https://github.com/orobogenius/sansdaemon which wasn't maintained anymore.

[![PHP from Packagist](https://img.shields.io/packagist/php-v/arxeiss/sansdaemon)](https://packagist.org/packages/arxeiss/sansdaemon)
[![Packagist Version](https://img.shields.io/packagist/v/arxeiss/sansdaemon)](https://packagist.org/packages/arxeiss/sansdaemon)
[![Build](https://github.com/arxeiss/sansdaemon/actions/workflows/lint-stan-test.yaml/badge.svg)](https://github.com/arxeiss/sansdaemon/actions/workflows/lint-stan-test.yaml)
[![codecov](https://codecov.io/gh/arxeiss/sansdaemon/graph/badge.svg?token=RUFUKN9AZL)](https://codecov.io/gh/arxeiss/sansdaemon)
[![PHP STAN](https://img.shields.io/badge/phpstan-level%208-informational)](https://github.com/phpstan/phpstan)

## Introduction
Batch process Laravel Queue without a daemon; Processes all jobs on the queue(s) and exits without running on daemon mode. This is useful in cases where you just want to process jobs on the queue and exit the worker process so they don't pile up in memory. Or when you want to run queue jobs on some routes.

## Installation

To install the latest version of SansDaemon, simply use composer

### Download

```bash
composer require arxeiss/sansdaemon
```

## Usage

SansDaemon is a console application that extends the functionality of laravel's `WorkCommand` - ```Illuminate\Queue\Console\WorkCommand```. _See_ [Laravel Queue](https://laravel.com/docs/queues) documentation.

To run the queue worker sans-daemon mode, simply add the ```--sansdaemon``` option to the original laravel queue worker command:

```bash
php artisan queue:work --sansdaemon
```

## Argument and Options

Since this package extends laravel's `WorkCommand`, it takes exactly all the arguments and options the original WorkCommand takes with three added options:

- `--sansdaemon` option tell the worker to process jobs on the queue without running in daemon mode.
- `--jobs` (default: 0, optional) - It allows you to specify the number of jobs to process each time the command runs. The default value `0` means it'll process all available jobs in the queue.
- `--max_exec_time` (default: `ini_get('max_execution_time') - 5s`, optional) - On some webhosts, your scripts will be killed, if it exceeds some amount of time. To prevent this behavior on really full queue, worker will stop after `--max_exec_time`. This is especially useful if you're running this command via your application's route or controller. See [Laravel Documentation](https://laravel.com/docs/artisan#programmatically-executing-commands) on how to run your queue programmatically.

#### Note on `--max_exec_time`

- `0` (zero) means the worker will run forever, which in this context means until the worker process is done. This is the default behavior when run from CLI.
- This option will not prevent `Maximum execution time exceeded` error, it'll try to avoid it by not running the next job on the queue if the script is reaching its [max_execution_time](http://php.net/manual/en/info.configuration.php#ini.max-execution-time).

## Contributing

Feel free to open PR, just make sure you pass all tests and checks.

```bash
composer test
composer lint
composer stan
```
