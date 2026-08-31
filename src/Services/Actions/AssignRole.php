<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Support\Facades\Config;
use Opscale\Actions\Action;
use Spatie\Permission\Models\Role;

final class AssignRole extends Action
{
    final public function identifier(): string
    {
        return 'assign-role';
    }

    final public function name(): string
    {
        return 'Assign Role';
    }

    final public function description(): string
    {
        return 'Assign an existing role to an user';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [
            [
                'name' => 'userId',
                'description' => 'The ID of the user',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'roleName',
                'description' => 'The name of the role to assign',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function outputs(): array
    {
        return [
            [
                'name' => 'message',
                'description' => 'Human-readable result of the role assignment',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    final public function handle(array $inputs = []): array
    {
        /** @var string $userId */
        $userId = $inputs['userId'];
        /** @var string $roleName */
        $roleName = $inputs['roleName'];

        /** @var class-string $userClass */
        $userClass = Config::get('auth.providers.users.model');

        $user = $userClass::find($userId);
        if (! $user) {
            return $this->fail(sprintf('User with ID "%s" not found.', $userId));
        }

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return $this->fail(sprintf('Role "%s" not found.', $roleName));
        }

        if ($user->hasRole($role)) {
            return $this->succeed([
                'message' => sprintf('User already has role "%s".', $roleName),
            ]);
        }

        $user->assignRole($role);

        return $this->succeed([
            'message' => sprintf('Role "%s" has been successfully assigned to user %s.', $roleName, $userId),
        ]);
    }
}
