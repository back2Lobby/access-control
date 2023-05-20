<?php

namespace Back2Lobby\AccessControl\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Assignable
{
    /**
     * Specify the user that the role will be assigned to the given roleable entity
     * - returns `true` if role was assigned successfully
     */
    public function to(Model $user): bool;
}
