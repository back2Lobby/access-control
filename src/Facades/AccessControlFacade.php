<?php

namespace Back2Lobby\AccessControl\Facades;

use Illuminate\Support\Facades\Facade;

class AccessControlFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return "access-control";
	}
}
