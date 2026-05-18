<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Opscale\Actions\Action;

final class CheckPermission extends Action
{
    final public function identifier(): string
    {
        return 'check-permission';
    }

    final public function name(): string
    {
        return 'Check Permission';
    }

    final public function description(): string
    {
        return 'Check if a user has a specific permission';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [
            [
                'name' => 'user',
                'description' => 'The authenticated user',
                'type' => 'object',
                'rules' => ['required'],
            ],
            [
                'name' => 'action',
                'description' => 'The permission action (Create, Read, Update, Delete, Execute)',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'resource',
                'description' => 'The resource name',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{success: bool, result: bool}
     */
    final public function handle(array $attributes = []): array
    {
        $this->fill($attributes);
        $validated = $this->validateAttributes();

        /** @var Authenticatable $user */
        $user = $validated['user'];
        /** @var string $action */
        $action = $validated['action'];
        /** @var string $resource */
        $resource = $validated['resource'];

        if ($this->shouldUseCache()) {
            $result = $this->checkWithCache($user, $action, $resource);
        } else {
            $result = $this->checkPermission($user, $action, $resource);
        }

        return [
            'success' => true,
            'result' => $result,
        ];
    }

    private function shouldUseCache(): bool
    {
        return (bool) Config::get('nova-authorization.cache', false);
    }

    private function checkWithCache(Authenticatable $user, string $action, string $resource): bool
    {
        $userId = (string) $user->getAuthIdentifier();
        $version = (int) Cache::get("opscale.authorization.user.{$userId}.v", 0);
        $cacheKey = $this->generateCacheKey($user, $action, $resource, $version);
        $ttl = (int) Config::get('nova-authorization.cache_ttl', 24);

        /** @var bool $result */
        $result = Cache::remember(
            $cacheKey,
            Carbon::now()->addHours($ttl),
            fn (): bool => $this->checkPermission($user, $action, $resource)
        );

        return $result;
    }

    private function checkPermission(Authenticatable $user, string $action, string $resource): bool
    {
        $permission = sprintf('%s %s', $action, $resource);

        if (method_exists($user, 'checkPermissionTo')) {
            return $user->checkPermissionTo($permission);
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }

    private function generateCacheKey(Authenticatable $user, string $action, string $resource, int $version = 0): string
    {
        $userId = (string) $user->getAuthIdentifier();
        $permission = sprintf('%s %s', $action, $resource);

        return "opscale.authorization.user.{$userId}.v{$version}." . str()->slug($permission, '.');
    }
}
