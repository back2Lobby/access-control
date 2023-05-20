<?php

namespace Back2Lobby\AccessControl\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Retractable
{
    /**
     * Specify the permission that will be allowed to the role
     * - returns `true` if permission was allowed successfully
     */
    public function from(Model $user): bool;
}
