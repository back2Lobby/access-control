<?php

namespace Back2Lobby\AccessControl\Service;

use App\Models\User;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Service\Contracts\Allowable;
use Back2Lobby\AccessControl\Store\Contracts\Storable;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Log;

class AllowPermission implements Allowable
{

	public function __construct(private readonly Storable $store, private readonly Role $role)
	{
	}

	public function to(Permission|string $permission): bool
	{
        if($permission = $this->store->getPermission($permission)){
			try {
				$query = DB::table("permission_role")->updateOrInsert(["permission_id" => $permission->id, "role_id" => $this->role->id], ["forbidden" => false]);

                if($query){
                    $this->store->sync(SyncFlag::OnlyMap);
                }

                return $query;
			} catch (QueryException $e) {
				Log::error("Couldn't attach permission $permission->name to the role " . $this->role->name . ": " . $e->getMessage());
			}
		}

		return false;
	}

	/**
	 * Assign super permission '*' to the role which allows it do do anything
	 *
	 */
	public function toDoEverything()
	{
		return $this->to("*");
	}
}
