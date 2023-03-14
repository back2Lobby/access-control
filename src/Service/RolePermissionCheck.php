<?php

namespace Back2Lobby\AccessControl\Service;

use App\Exceptions\InvalidPermissionException;
use App\Models\User;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Contracts\Storable;
use DB;
use Illuminate\Database\Eloquent\Model;

class RolePermissionCheck
{
	public function __construct(private readonly Storable $store, private readonly Role $role)
	{}

	/**
	 * Check if role have a specific permission
	 *
	 */
    public function do(Permission|string $permission): bool
    {
        if($permission = $this->store->getPermission($permission)){
            return $this->store->canRoleDo($this->role, $permission);
        }else{
            return false;
        }
    }
}
