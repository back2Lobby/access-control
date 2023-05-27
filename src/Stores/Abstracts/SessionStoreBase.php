<?php

namespace Back2Lobby\AccessControl\Stores\Abstracts;

use Back2Lobby\AccessControl\Stores\Contracts\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class SessionStoreBase implements Storable
{
    private static SessionStoreBase $store;

    private function __construct()
    {
    }

    public static function getInstance(): SessionStoreBase
    {
        if (! isset(self::$store)) {
            self::$store = new static;
        }

        return self::$store;
    }

    private function __clone()
    {
    }

    /**
     * Set the given user as the correctly authenticated user
     */
    abstract public function setAuthUser(Model $user): void;

    /**
     * Get the currently authenticated user
     */
    abstract public function getAuthUser(): Model|null;

    // /**
    //  * Specify class for authenticated user object
    //  */
    // abstract public function setAuthUserModel(string $modelClassName): void;

    /**
     * Get the specified class for authenticated user object
     */
    abstract public function getAuthUserModel(): string;

    /**
     * Get the table name being used by the auth user model
     */
    abstract public function getAuthUserTable(): string;

    /**
     * Check if the given user is currently authenticated or not
     *
     * @param  bool  $throwException throw exception if $user is invalid
     */
    abstract public function isAuthUser(Model $user, bool $throwException = true): bool;

    /**
     * Check if given user is an object of class specified for authenticated user
     */
    abstract public function isValidUser(Model $user, bool $throwException = true): bool;

    /**
     * Get collection AssignedRole model objects for the authenticated user if available and also store it
     */
    abstract public function getAssignedRoles(): Collection|null;

    /**
     * Removes the AssignedRoles data for authenticated user from memory
     */
    abstract public function clearAssignedRoles(): void;

    /**
     * Resets the authenticated user to null
     */
    abstract public function resetAuthUser(): void;
}
