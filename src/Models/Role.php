<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Factories\RoleFactory;
use Back2Lobby\AccessControl\Traits\syncOnEvents;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $title
 * @property array|null $roleables
 *
 * @method static RoleFactory factory($count = null, $state = [])
 */
class Role extends Model
{
    use HasFactory, syncOnEvents;

    protected $guarded = [];

    protected $casts = [
        'roleables' => 'array',
    ];

    public function allow(Permission $permission): bool
    {
        return AccessControl::allow($this)->to($permission);
    }

    public function disallow(Permission $permission): bool
    {
        return AccessControl::disallow($this)->to($permission);
    }

    public function forbid(Permission $permission): bool
    {
        return AccessControl::forbid($this)->to($permission);
    }

    public function permissions(): Collection
    {
        return AccessControl::getStore()->getAllPermissionsOf($this);
    }

    public function users(Model $roleable = null): Builder
    {
        return User::whereIs($this, $roleable);
    }

    /**
     * Get roleable if it's valid for the target role
     *
     * @throws InvalidRoleableException if given role is invalid or not found in database
     */
    public static function getValidRoleable($role, $roleable): Model|null
    {
        if (is_array($role->roleables) && count($role->roleables) > 0) {
            if (isset($roleable->id) && is_numeric($roleable->id) && in_array($roleable::class, $role->roleables)) {
                return $roleable;
            } else {
                if (is_null($roleable)) {
                    throw new InvalidRoleableException('Target role `'.$role->name."` needs a valid roleable, null isn't acceptable.");
                } else {
                    throw new InvalidRoleableException('Provided roleable cannot be validated because its either invalid or not found');
                }
            }
        } else {
            if (is_null($roleable)) {
                return null;
            } else {
                throw new InvalidRoleableException('Provided roleable cannot be validated because the target role `'.$role->name."` doesn't allow any roleables");
            }
        }
    }
}
