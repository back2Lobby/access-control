<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Models\Permission;

interface Forbiddable
{
    /**
     * Specify the permission that will be forbidden to the role
     * - returns `true` if permission was allowed successfully
     */
    public function to(Permission|string $permission): bool;
}
