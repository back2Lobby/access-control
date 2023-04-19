<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Service\AccessService;
use Back2Lobby\AccessControl\Service\Contracts\Accessable;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;

class AccessControlService
{
    private static Accessable $access;

    public function __construct(Storable $store)
    {
        self::$access = new AccessService($store);
    }

    public function __call($method_name, $args)
    {
        return call_user_func_array([self::$access, $method_name], $args);
    }

    public static function __callStatic($method_name, $args)
    {
        return call_user_func_array([self::$access, $method_name], $args);
    }
}
