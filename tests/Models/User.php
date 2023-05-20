<?php

namespace Back2Lobby\AccessControl\Tests\Models;

use Back2Lobby\AccessControl\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles, HasFactory;
}
