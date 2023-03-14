<?php

namespace Back2Lobby\AccessControl\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use RuntimeException;

class Role extends Model
{
	use HasFactory;

	protected $casts = [
		'roleables' => 'array'
	];

	public function permissions()
	{
		return $this->belongsToMany(Permission::class)->using(PermissionRole::class);
	}

	public function users()
	{
		return $this->belongsToMany(User::class)->using(RoleUser::class);
	}
}
