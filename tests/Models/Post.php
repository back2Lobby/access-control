<?php

namespace Back2Lobby\AccessControl\Tests\Models;

use Back2Lobby\AccessControl\Traits\isRoleable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use isRoleable, HasFactory;
}
