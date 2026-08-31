<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Support\Facades\Config;
use Opscale\Actions\Action;

final class CreateRole extends Action
{
    final public function identifier(): string
    {
        return 'create-role';
    }

    final public function name(): string
    {
        return 'Create Role';
    }

    final public function description(): string
    {
        return 'Create a new role with permissions for Nova resources';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [
            [
                'name' => 'name',
                'description' => 'The name of the role',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'permissions',
                'description' => 'Permission codes per resource (format: ResourceName:CRUDX)',
                'type' => 'array',
                'rules' => ['required', 'array'],
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
                'description' => 'Human-readable result of the role creation',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'role',
                'description' => 'The name of the created role',
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
        /** @var string $roleName */
        $roleName = $inputs['name'];
        /** @var array<string> $permissions */
        $permissions = $inputs['permissions'];

        $role = config('permission.models.role')::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $this->assignPermissions($role, $permissions);

        return $this->succeed([
            'message' => sprintf('Role "%s" created successfully.', $roleName),
            'role' => $roleName,
        ]);
    }

    /**
     * @param  array<string>  $permissions
     */
    private function assignPermissions($role, array $permissions): void
    {
        $permissionsMap = [
            'C' => __('Create'),
            'R' => __('Read'),
            'U' => __('Update'),
            'D' => __('Delete'),
            'X' => __('Execute'),
        ];

        /** @var array<class-string> $resources */
        $resources = Config::get('nova-authorization.resources', []);
        $resourceMap = [];

        foreach ($resources as $resource) {
            $resourceMap[$resource::singularLabel()] = $resource;
        }

        foreach ($permissions as $resourcePermission) {
            $parts = explode(':', trim($resourcePermission));
            if (count($parts) !== 2) {
                continue;
            }

            [$resourceName, $codes] = $parts;
            $resourceName = trim($resourceName);

            if (! isset($resourceMap[$resourceName])) {
                continue;
            }

            foreach (str_split(strtoupper($codes)) as $letter) {
                if (array_key_exists($letter, $permissionsMap)) {
                    $permissionName = $permissionsMap[$letter] . ' ' . $resourceName;

                    $permission = config('permission.models.permission')::query()->firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);

                    $permission->assignRole($role);
                }
            }
        }
    }
}
