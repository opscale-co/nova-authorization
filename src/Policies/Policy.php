<?php

namespace Opscale\NovaAuthorization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Opscale\NovaAuthorization\Contracts\HasPrivileges;
use Opscale\NovaAuthorization\Services\Actions\CheckPermission;

abstract class Policy
{
    use HandlesAuthorization;

    abstract public function getResource(): string;

    /**
     * @param  mixed  $user
     * @param  string  $ability
     */
    final public function before($user, $ability): ?bool
    {
        if ($user != null &&
            $user instanceof HasPrivileges &&
            $user->isSuperAdmin()) {
            return $user->isSuperAdmin();
        }

        return null;
    }

    /**
     * @param  Authenticatable  $user
     */
    final public function create($user): bool
    {
        return $this->can($user, null, __('Create'));
    }

    /**
     * @param  Authenticatable  $user
     */
    final public function viewAny($user): bool
    {
        return $this->can($user, null, __('Read'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function viewOwn($user, $model): bool
    {
        return $this->can($user, $model, __('Read'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function view($user, $model): bool
    {
        if ($this->checkUser($user, $model)) {
            return $this->viewOwn($user, $model);
        }

        return $this->can($user, $model, __('Read'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function updateOwn($user, $model): bool
    {
        return $this->can($user, $model, __('Update'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function update($user, $model): bool
    {
        if ($this->checkUser($user, $model)) {
            return $this->updateOwn($user, $model);
        }

        return $this->can($user, $model, __('Update'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function deleteOwn($user, $model): bool
    {
        return $this->can($user, $model, __('Delete'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function delete($user, $model): bool
    {
        if ($this->checkUser($user, $model)) {
            return $this->deleteOwn($user, $model);
        }

        return $this->can($user, $model, __('Delete'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function runOwnAction($user, $model): bool
    {
        return $this->can($user, $model, __('Execute'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function runAction($user, $model): bool
    {
        if ($this->checkUser($user, $model)) {
            return $this->runOwnAction($user, $model);
        }

        return $this->can($user, $model, __('Execute'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function restore($user, $model): bool
    {
        return $this->can($user, $model, __('Create'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final public function forceDelete($user, $model): bool
    {
        return $this->can($user, $model, __('Delete'));
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final protected function can($user, $model, string $action): bool
    {
        $result = CheckPermission::run([
            'user' => $user,
            'action' => $action,
            'resource' => $this->getResource(),
        ]);

        return (bool) ($result->data()['result'] ?? false);
    }

    /**
     * @param  Authenticatable  $user
     * @param  mixed  $model
     */
    final protected function checkUser($user, $model): bool
    {
        /** @var class-string|null $userClass */
        $userClass = Config::get('auth.providers.users.model');
        if (! $userClass || ! is_object($model)) {
            return false;
        }

        $predicate = $model instanceof $userClass ? 'id' : 'user_id';

        if (! property_exists($model, $predicate)) {
            return false;
        }

        return isset($model->$predicate) && $user->getKey() == $model->$predicate;
    }
}
