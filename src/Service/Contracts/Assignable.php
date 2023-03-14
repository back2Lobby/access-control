<?php

namespace Back2Lobby\AccessControl\Service\Contracts;

use App\Exceptions\InvalidUserException;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

interface Assignable
{
	/**
	 * Specify the user that the role will be assigned to the given roleable entity
	 * - returns `true` if role was assigned successfully
	 *
	 */
	public function to(User|int $user): bool;
}
