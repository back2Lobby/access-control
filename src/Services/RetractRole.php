<?php

namespace Back2Lobby\AccessControl\Services;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Services\Contracts\Retractable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetractRole implements Retractable
{
    public function __construct(private readonly Role $role, private Model|null $roleable = null)
    {
    }

    public function from(Model $user): bool
    {
        if (AccessControlFacade::isValidUser($user, true)) {
            $this->roleable = Role::getValidRoleable($this->role, $this->roleable);
            try {

                $userColumnName = Str::singular(AccessControlFacade::getAuthUserTable()).'_id';

                if (is_null($this->roleable)) {
                    $query = DB::table('assigned_roles')->where([$userColumnName => $user->id, 'role_id' => $this->role->id, 'roleable_id' => 0, 'roleable_type' => ''])->delete();
                } else {
                    $query = DB::table('assigned_roles')->where([
                        $userColumnName => $user->id,
                        'role_id' => $this->role->id,
                        'roleable_id' => $this->roleable->id,
                        'roleable_type' => $this->roleable::class,
                    ])->delete();
                }

                // reset the loaded user roles
                AccessControlFacade::getSessionStore()->clearAssignedRoles();

                return $query;
            } catch (QueryException $e) {
                Log::error("Couldn't retract role ".$this->role->name." from the user $user->name :".$e->getMessage());
            }
        }

        return false;
    }
}
