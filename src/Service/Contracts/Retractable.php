<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use Back2Lobby\AccessControl\Exceptions\InvalidPermissionException;
use App\Models\User;

interface Retractable
{
	/**
	 * Specify the permission that will be allowed to the role
	 * - returns `true` if permission was allowed successfully
	 *
	 */
	public function from(User $user): bool;
}
