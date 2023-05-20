<?php

namespace Back2Lobby\AccessControl\Stores\Contracts;

interface Storable
{
    public static function getInstance(): Storable;
}
