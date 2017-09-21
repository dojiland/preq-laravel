<?php

namespace Per3evere\Preq\Facades;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return void
     */
    protected static function getFacadeAccessor()
    {
        return 'preq';
    }
}
