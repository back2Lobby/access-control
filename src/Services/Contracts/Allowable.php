<?php

namespace Back2Lobby\AccessControl\Services\Contracts;

use Back2Lobby\AccessControl\Models\Permission;

interface Allowable
{
    /**
     * Specify the permission that will be allowed to the role
     * - returns `true` if permission was allowed successfully
     */
    public function to(Permission|string $permission): bool;
}
