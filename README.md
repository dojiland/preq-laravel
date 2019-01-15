[![Build Status](https://travis-ci.org/persevereVon/preq-laravel.svg?branch=master)](https://travis-ci.org/persevereVon/preq-laravel)

## About Preq

Preq is a latency and fault tolerance library for Laravel && Lumen, inspired by Netflix’s Hystrix and [upwork/phystrix](https://github.com/upwork/phystrix)

## Installation

Require this package with composer:

```
composer require per3evere/preq --dev
```

Add ServiceProvider

### Laravel

add this to the providers array in `config/app.php`
```php
Per3evere\Preq\PreqServiceProvider::class
```

### Lumen

add this in `bootstrap/app.php`
```php
$app->register(Per3evere\Preq\PreqServiceProvider::class);
```


## Usage

Create service command file

``` php
namespace App\Services;

use Per3evere\Preq\AbstractCommand;

class Example extends AbstractCommand
{
    /**
     * 同步执行命令.
     *
     * @return void
     */
    public function run()
    {
        return 'run!';
    }

    /**
     * 异步执行命令.
     *
     * @return \Guzzlehttp\Promise\Promise;
     */
    public function runAsync()
    {
        // 返回注意返回类型.
    }
}
```

execute it

```php
$command = app('preq')->getCommand(\App\Services\Example::class);

// 同步执行命令
echo $command->execute();

// 异步执行命令
$command->queue();
```

