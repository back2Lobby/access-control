<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Models\Permission;

interface Disallowable
{
    /**
     * Specify the permission that will be disallowed to the role
     * - returns `true` if permission was disallowed successfully
     */
    public function to(Permission|string $permission): bool;
}
