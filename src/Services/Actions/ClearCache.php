<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Support\Facades\Cache;
use Opscale\Actions\Action;
use Override;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

final class ClearCache extends Action
{
    final public function identifier(): string
    {
        return 'clear-cache';
    }

    final public function name(): string
    {
        return 'Clear Cache';
    }

    final public function description(): string
    {
        return 'Delete cached permissions for a user';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [
            [
                'name' => 'userId',
                'description' => 'User ID to clear cache for',
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
                'description' => 'Human-readable result of the cache clearing',
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

        Cache::increment("opscale.authorization.user.{$userId}.v");

        return $this->succeed([
            'message' => 'Authorization cache cleared successfully.',
        ]);
    }

    /**
     * Map the Spatie role event to the Action's declared parameters. The base
     * adapter cannot derive `userId` from the event's public props (`model`,
     * `rolesOrIds`), so we resolve it from the affected model's key.
     *
     * @param  array<int|string, mixed>  $arguments
     * @return array<string, mixed>
     */
    #[Override]
    protected function mapEventToInputs(array $arguments): array
    {
        $inputs = parent::mapEventToInputs($arguments);

        $event = $arguments[0] ?? null;

        if ($event instanceof RoleAttachedEvent || $event instanceof RoleDetachedEvent) {
            $key = $event->model->getKey();

            if (is_scalar($key)) {
                $inputs['userId'] = (string) $key;
            }
        }

        return $inputs;
    }
}
