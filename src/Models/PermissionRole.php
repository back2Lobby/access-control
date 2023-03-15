<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;
use Back2Lobby\AccessControl\Traits\syncOnEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

/**
 * @property int $permission_id
 * @property int $role_id
 * @property bool $forbidden
 */
class PermissionRole extends Pivot
{
    use HasFactory, syncOnEvents;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Get the rows that allow given permission
     *
     * @param Permission $permission
     * @param Permission|null $superPermission
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function wherePermissionIsAllowed(Permission $permission, Permission $superPermission = null): \Illuminate\Database\Eloquent\Builder
    {
        return PermissionRole::from("permission_role as pr")
            ->select("pr.*")
            ->where(function ($q) use ($permission, $superPermission) {
                if ($superPermission) {
                    $q->where("pr.permission_id", $superPermission->id);
                }
                $q->orWhere("pr.permission_id", $permission->id);
            })->whereNotExists(function ($q) use ($permission) {
                $q->select(DB::raw(1))
                    ->from("permission_role as pr2")
                    ->whereRaw("pr2.role_id = pr.role_id")
                    ->where("pr2.permission_id", $permission->id)
                    ->where("pr2.forbidden", 1);
            });
    }
}
