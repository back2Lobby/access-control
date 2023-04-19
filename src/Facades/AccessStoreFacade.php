<?php

namespace Back2Lobby\AccessControl\Facades;

use Illuminate\Support\Facades\Facade;

class AccessStoreFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'access-store';
    }
}
