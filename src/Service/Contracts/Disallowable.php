<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use App\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Models\Permission;

interface Disallowable
{
	/**
	 * Specify the permission that will be disallowed to the role
	 * - returns `true` if permission was disallowed successfully
	 *namespace App\Services\AccessControl\Models;
	 */
	public function to(Permission|string $permission): bool;
}
