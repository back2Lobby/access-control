<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Back2Lobby\AccessControl\Traits\syncOnEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
	use HasFactory, syncOnEvents;

    public function roles()
	{
		return $this->belongsToMany(Role::class)->using(PermissionRole::class);
	}
}
