<?php

namespace Back2Lobby\AccessControl\Store\Enumerations;

enum SyncFlag
{
    case SyncAll;
    case OnlyRole;
    case OnlyPermission;
    case OnlyMap;
}
