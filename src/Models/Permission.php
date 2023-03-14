<?php

namespace Back2Lobby\AccessControl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
	use HasFactory;

	public function roles()
	{
		return $this->belongsToMany(Role::class)->using(PermissionRole::class);
	}
}
