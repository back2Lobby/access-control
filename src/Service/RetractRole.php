<?php

namespace Back2Lobby\AccessControl\Service;

use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\User;
use Back2Lobby\AccessControl\Service\Contracts\Retractable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetractRole implements Retractable
{
    public function __construct(private readonly Role $role, private Model|null $roleable = null)
    {
    }

    public function from(User|int $user): bool
    {
        if ($user = User::getValidUser($user)) {
            $this->roleable = Role::getValidRoleable($this->role, $this->roleable);
            try {
                if (is_null($this->roleable)) {
                    return DB::table('role_user')->where(['user_id' => $user->id, 'role_id' => $this->role->id, 'roleable_id' => 0, 'roleable_type' => ''])->delete();
                } else {
                    return DB::table('role_user')->where([
                        'user_id' => $user->id,
                        'role_id' => $this->role->id,
                        'roleable_id' => $this->roleable->id,
                        'roleable_type' => $this->roleable::class,
                    ])->delete();
                }
            } catch (QueryException $e) {
                Log::error("Couldn't retract role ".$this->role->name." from the user $user->name :".$e->getMessage());
            }
        }

        return false;
    }
}
