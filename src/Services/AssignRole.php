<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Services\Contracts\Assignable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssignRole implements Assignable
{
    public function __construct(private readonly Role $role, private Model|null $roleable = null)
    {
    }

    public function to(Model $user): bool
    {
        if (AccessControlFacade::getSessionStore()->isValidUser($user, true)) {
            $this->roleable = Role::getValidRoleable($this->role, $this->roleable);
            try {

                $userColumnName = Str::singular(AccessControlFacade::getAuthUserTable()).'_id';

                if (is_null($this->roleable)) {
                    $query = DB::table('assigned_roles')->insert([$userColumnName => $user->id, 'role_id' => $this->role->id]);
                } else {
                    $query = DB::table('assigned_roles')->insert([
                        $userColumnName => $user->id,
                        'role_id' => $this->role->id,
                        'roleable_id' => $this->roleable->id,
                        'roleable_type' => $this->roleable::class,
                    ]);
                }

                // reset the loaded user roles
                AccessControlFacade::getSessionStore()->clearAssignedRoles();

                return $query;
            } catch (QueryException $e) {
                Log::error("Couldn't assign role ".$this->role->name." to the user $user->name :".$e->getMessage());
            }
        }

        return false;
    }
}
