<?php

namespace Back2Lobby\AccessControl\Traits;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Models\Permission;
use Back2Lobby\AccessControl\Models\PermissionRole;
use Back2Lobby\AccessControl\Models\Role;
use Back2Lobby\AccessControl\Store\Enumerations\SyncFlag;

trait syncOnEvents
{
    protected static function booted(): void
    {
        $flag = match (static::class) {
            Role::class => SyncFlag::OnlyRole,
            Permission::class => SyncFlag::OnlyPermission,
            PermissionRole::class => SyncFlag::OnlyMap,
            default => SyncFlag::SyncAll
        };

        // sync roles and permissions again on these events
        $events = [
            'saved', 'created', 'updated', 'deleted',
        ];

        foreach ($events as $event) {
            static::{$event}(function ($model) use ($flag) {
                AccessControlFacade::getStore()->sync($flag);
            });
        }

    }
}
