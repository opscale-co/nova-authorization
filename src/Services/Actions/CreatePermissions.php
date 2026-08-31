<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Opscale\Actions\Action;
use Spatie\Permission\Contracts\Permission;

final class CreatePermissions extends Action
{
    final public function identifier(): string
    {
        return 'create-permissions';
    }

    final public function name(): string
    {
        return 'Create Permissions';
    }

    final public function description(): string
    {
        return 'Create permissions for all configured Nova resources';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [];
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function outputs(): array
    {
        return [
            [
                'name' => 'message',
                'description' => 'Human-readable result of the permission creation',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'created',
                'description' => 'Resource labels for which permissions were created',
                'type' => 'array',
                'rules' => ['array'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    final public function handle(array $inputs = []): array
    {
        /** @var array<class-string> $resources */
        $resources = Config::get('nova-authorization.resources', []);

        if (empty($resources)) {
            return $this->fail('No resources configured. Please add resources to nova-authorization config.');
        }

        /** @var class-string<Model&Permission> $permissionClass */
        $permissionClass = Config::get('permission.models.permission');

        $permissions = [
            __('Create'),
            __('Read'),
            __('Update'),
            __('Delete'),
            __('Execute'),
        ];

        $created = [];

        foreach ($resources as $resource) {
            $resourceName = $resource::singularLabel();
            foreach ($permissions as $permission) {
                $name = $permission . ' ' . $resourceName;
                $permissionClass::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }
            $created[] = $resourceName;
        }

        return $this->succeed([
            'message' => 'Permissions created successfully.',
            'created' => $created,
        ]);
    }
}
