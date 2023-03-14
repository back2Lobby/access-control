<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use App\Exceptions\InvalidPermissionException;
use Back2Lobby\AccessControl\Models\Permission;

interface Allowable
{
	/**
	 * Specify the permission that will be allowed to the role
	 * - returns `true` if permission was allowed successfully
	 *
	 */
	public function to(Permission|string $permission): bool;
}
