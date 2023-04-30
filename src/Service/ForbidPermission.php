<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Service\Contracts\Forbiddable;
use Back2Lobby\AccessControl\Store\Abstracts\Storable;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForbidPermission implements Forbiddable
{
    public function __construct(private readonly Storable $store, private readonly Role $role)
    {
    }

    public function to(Permission|string $permission): bool
    {
        if ($permission = $this->store->getPermission($permission)) {
            try {
                $query = DB::table('permission_role')->updateOrInsert(['permission_id' => $permission->id, 'role_id' => $this->role->id], ['forbidden' => true]);

                if ($query) {
                    $this->store->sync(SyncFlag::OnlyMap);
                }

                return $query;
            } catch (QueryException $e) {
                Log::error("Couldn't forbid permission $permission->name to the role ".$this->role->name.': '.$e->getMessage());
            }
        }

        return false;
    }

    /**
     * Forbid super permission '*' to the role which forbids it to do anything except specifically allowed
     */
    public function superPermission(): bool
    {
        if ($this->store->getPermission('*') === null) {
            AccessControlFacade::createPermission([
                'name' => '*',
                'title' => 'Super Permission',
            ]);
        }

        return $this->to('*');
    }
}
