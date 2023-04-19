<?php

namespace Back2Lobby\AccessControl\Models;

use Back2Lobby\AccessControl\Facades\AccessControlFacade as AccessControl;
use Back2Lobby\AccessControl\Factories\PermissionFactory;
use Back2Lobby\AccessControl\Traits\syncOnEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property array|null $roleables
 *
 * @method static PermissionFactory factory($count = null, $state = [])
 */
class Permission extends Model
{
    use HasFactory, syncOnEvents;

    protected $guarded = [];

    public function roles(): Collection
    {
        return AccessControl::getStore()->getAllRolesOf($this);
    }
}
