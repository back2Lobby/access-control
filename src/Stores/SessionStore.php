<?php

namespace Back2Lobby\AccessControl\Stores;

use Back2Lobby\AccessControl\Exceptions\InvalidUserException;
use Back2Lobby\AccessControl\Models\AssignedRole;
use Back2Lobby\AccessControl\Stores\Abstracts\SessionStoreBase;
use Back2Lobby\AccessControl\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SessionStore extends SessionStoreBase
{
    private ?string $authUserClassName = null;

    private ?Model $authUser = null;

    private ?Collection $assignedRoles = null;

    public function setAuthUser(Model $user): void
    {
        if (
            auth()->user() &&
            get_class($user) === $this->getAuthUserModel() &&
            ! is_null($user->getKey())
        ) {
            $this->authUser = $user;

            // make sure it resets the role user maps if the user is changed
            $this->clearAssignedRoles();
        }
    }

    public function getAuthUser(): Model|null
    {
        return $this->authUser;
    }

    private function setAuthUserModel(string $modelClassName): void
    {
        if (! class_exists($modelClassName)) {
            throw new InvalidUserException('Given class for user model is does not exist.');
        }

        if (
            ! is_subclass_of($modelClassName, 'Illuminate\Database\Eloquent\Model')
        ) {
            throw new InvalidUserException("Given class doesn't extend `Illuminate\Database\Eloquent\Model`");
        }

        if (
            ! is_subclass_of($modelClassName, 'Illuminate\Contracts\Auth\Authenticatable') ||
            ! is_subclass_of($modelClassName, 'Illuminate\Contracts\Auth\Access\Authorizable')
        ) {
            throw new InvalidUserException("Given class doesn't implement `Illuminate\Contracts\Auth\Authenticatable` and `Illuminate\Contracts\Auth\Access\Authorizable`.");
        }

        if (trait_exists(HasRoles::class) && ! in_array(HasRoles::class, class_uses($modelClassName))) {
            throw new InvalidUserException("Given class doesn't use the trait `".HasRoles::class.'`');
        }

        $this->authUserClassName = $modelClassName;
    }

    public function getAuthUserModel(): string
    {
        if (is_null($this->authUserClassName)) {
            $this->setAuthUserModel(config('access.auth_user_model'));
        }

        return $this->authUserClassName;
    }

    public function getAuthUserTable(): string
    {
        $userModel = $this->getAuthUserModel();

        return (new $userModel)?->getTable();
    }

    public function isAuthUser(Model $user, bool $throwException = true): bool
    {
        if (! $this->isValidUser($user, $throwException)) {
            return false;
        }

        return $this->getAuthUser() && $this->getAuthUser()->getKey() === $user->getKey();
    }

    public function isValidUser(Model $user, bool $throwException = true): bool
    {
        $isValid = $user->getKey() && $this->getAuthUserModel() === get_class($user);

        if ($throwException && ! $isValid) {
            throw new InvalidUserException('Provided user cannot be validated because its either invalid or not found in database');
        }

        return $isValid;
    }

    public function getAssignedRoles(): Collection|null
    {
        if ($this->authUser && ! isset($this->assignedRoles)) {

            $userColumnName = Str::singular($this->getAuthUserTable()).'_id';

            $this->assignedRoles = AssignedRole::where($userColumnName, $this->authUser->id)->get();
        }

        return $this->assignedRoles;
    }

    public function clearAssignedRoles(): void
    {
        $this->assignedRoles = null;
    }

    public function resetAuthUser(): void
    {
        $this->authUser = null;
        $this->assignedRoles = null;
    }
}
