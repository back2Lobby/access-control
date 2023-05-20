<?php

namespace Back2Lobby\AccessControl\Stores\Enumerations;

enum SyncFlag
{
    case Everything;
    case OnlyRoles;
    case OnlyPermissions;
    case OnlyMaps;
}
