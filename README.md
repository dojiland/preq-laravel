[![Build Status](https://travis-ci.org/persevereVon/preq-laravel.svg?branch=master)](https://travis-ci.org/persevereVon/preq-laravel)

## About Preq

Encapsulate communication of services.

## Installation

```javascript
"require": {
    "per3evere/preq": "^0.1.0@dev"
}
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

