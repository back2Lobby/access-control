<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Models\User;

interface Retractable
{
    /**
     * Specify the permission that will be allowed to the role
     * - returns `true` if permission was allowed successfully
     */
    public function from(User|int $user): bool;
}
