[![Build Status](https://travis-ci.org/persevereVon/preq-laravel.svg?branch=master)](https://travis-ci.org/persevereVon/preq-laravel)

## About Preq

Encapsulate communication of services.

**!!! Currently in beta and should not be used in production.**

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
     * 执行命令
     *
     * @return void
     */
    public function execute()
    {
        return 'executed!';
    }
}
```

execute it

```php
$command = app('preq')->getCommand(\App\Services\Example::class);
echo $command->execute();
```

