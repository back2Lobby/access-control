<?php

namespace Back2Lobby\AccessControl\Traits;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Exceptions\InvalidRoleException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Models\Permission;
use App\Models\User;
use Back2Lobby\AccessControl\Models\RoleUser;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait hasRolesAndPermissions
{

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->using(RoleUser::class);
    }

    /**
     * Get all the permission of this user from any role combined
     *
     * @returns Collection
     */
    public function permissions(): Collection
    {
        // get all the roles of this users
        $roles = RoleUser::where("user_id",$this->id)->get(["role_id"])->pluck("role_id");

        // get all the permission of each role
        $permissions = $roles->map(fn($r) => AccessControlFacade::getStore()->getRole($r))
            ->filter(fn($r) => ! is_null($r))
            ->map(fn($r) => AccessControlFacade::getStore()->getAllowedPermissionsOf($r));

        return $permissions->flatten()->unique();
    }

    public function assign(Role|string $role, Model $roleable = null): bool
    {
        return AccessControlFacade::assign($role, $roleable)->to($this);
    }

    /**
     * Get users having specified role for the specified roleable
     * - if nothing is given as roleable in 2nd argument then it will only check roles that don't have any roleables attached
     *
     */
    public static function whereIs(Role|string $role, Model $roleable = null): Builder
    {
        if ($role = AccessControlFacade::getStore()->getRole($role)) {
            return User::whereHas("roles", function ($q) use ($role, $roleable) {
                $q->where("role_id", $role->id);
                if ($roleable && $roleable->id) {
                    $q->where("roleable_id", $roleable->id)->where("roleable_type", $roleable::class);
                } else {
                    $q->where("roleable_id", 0)->where("roleable_type", "");
                }
            });
        } else {
            throw new InvalidRoleException("Provided role cannot be validated because its either invalid or not found in database");
        }
    }

    /**
     * Get all the users with specified permission on a given roleable
     *
     * @param Permission|string $permission Permission to check
     * @param Model|null $roleable Target roleable model e.g. company
     * @param bool $includeIndirectRoles Include the users who have the permission indirectly like super-admin
     *
     * @throws InvalidPermissionException
     *
     * @returns \Illuminate\Database\Eloquent\Builder
     */
    public static function whereCan(Permission|string $permission,Model $roleable=null,bool $includeIndirectRoles = false): \Illuminate\Database\Eloquent\Builder
    {
        if ($permission = AccessControlFacade::getStore()->getPermission($permission)) {

            $users = User::query();

            // get all roles that are directly allowed for this permission
            $roles = AccessControlFacade::getStore()->getAllowedRolesOf($permission);

            // making sure that we get only roles that allow this roleable
            $roles = $roles->filter(function($r) use($roleable){
                return $roleable && is_array($r->roleables) && in_array($roleable::class,$r->roleables);
            });

            if($includeIndirectRoles) {
                $roles = $roles->concat(AccessControlFacade::getStore()->getIndirectRolesOf($permission));
            }

            // if no roles available then no need to go any further
            if ($roles->count() <= 0) return $users->whereNull("id");

            // get matching user roles
            $usersQuery = RoleUser::whereIn("role_id",$roles->pluck("id"));

            // filter it based on roleable
            $usersQuery->where(function($q) use($roleable,$includeIndirectRoles){

                if($roleable && $roleable->id){
                    $q->where(function($qq) use($roleable){
                        $qq->where("roleable_type",$roleable::class)
                        ->where("roleable_id",$roleable->id);
                    });
                }

                if($includeIndirectRoles) {
                    $q->orWhere(function ($qq){
                        $qq->where("roleable_type","")
                            ->where("roleable_id",0);
                    });
                }
            });

            // return users by joining it with users table
            return User::distinct()->select("users.*")->joinSub(
                $usersQuery,
                "matched_role_user",
                fn($j) => $j->on("matched_role_user.user_id","=","users.id")
            );
        }else{
            throw new InvalidPermissionException("Provided permission cannot be validated because its either invalid or not found in database");
        }
    }
}
