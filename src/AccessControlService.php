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
        return self::forwardCall($method_name, $args);
    }

    public static function __callStatic($method_name, $args)
    {
        return self::forwardCall($method_name, $args);
    }

    private static function forwardCall($method_name, $args)
    {
        if (method_exists(self::$access->getStore(), $method_name)) {
            return call_user_func_array(
                [self::$access->getStore(), $method_name],
                $args
            );
        } elseif (method_exists(self::$access, $method_name)) {
            return call_user_func_array(
                [self::$access, $method_name],
                $args
            );
        }

        throw new \TypeError(self::class.' does not have any method named `'.$method_name.'`');
    }
}
