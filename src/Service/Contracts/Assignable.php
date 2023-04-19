<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Models\User;

interface Assignable
{
    /**
     * Specify the user that the role will be assigned to the given roleable entity
     * - returns `true` if role was assigned successfully
     */
    public function to(User|int $user): bool;
}
