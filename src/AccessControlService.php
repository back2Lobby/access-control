<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Services\AccessService;
use Back2Lobby\AccessControl\Services\Contracts\Accessable;
use Back2Lobby\AccessControl\Stores\Abstracts\CacheStoreBase;
use Back2Lobby\AccessControl\Stores\Abstracts\SessionStoreBase;

class AccessControlService
{
    private static Accessable $access;

    public function __construct(CacheStoreBase $cacheStore, SessionStoreBase $sessionStore)
    {
        self::$access = new AccessService($cacheStore, $sessionStore);
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
        if (method_exists(self::$access, $method_name)) {
            return call_user_func_array(
                [self::$access, $method_name],
                $args
            );
        } elseif (method_exists(self::$access->getCacheStore(), $method_name)) {
            return call_user_func_array(
                [self::$access->getCacheStore(), $method_name],
                $args
            );
        } elseif (method_exists(self::$access->getSessionStore(), $method_name)) {
            return call_user_func_array(
                [self::$access->getSessionStore(), $method_name],
                $args
            );
        }

        throw new \TypeError(self::class.' does not have any method named `'.$method_name.'`');
    }
}
