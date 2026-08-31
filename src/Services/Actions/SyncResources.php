<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Opscale\Actions\Action;

final class SyncResources extends Action
{
    private Application $application;

    /**
     * @var array<int, class-string>
     */
    private array $discoveredResources = [];

    final public function __construct(Application $application)
    {
        $this->application = $application;
    }

    final public function identifier(): string
    {
        return 'sync-resources';
    }

    final public function name(): string
    {
        return 'Sync Resources';
    }

    final public function description(): string
    {
        return 'Sync Nova resources to nova-authorization config file';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [
            [
                'name' => 'filter',
                'description' => 'Filter resources by namespace or class name (comma-separated)',
                'type' => 'string',
                'rules' => ['nullable', 'string'],
            ],
            [
                'name' => 'exclude',
                'description' => 'Exclude specific resources (comma-separated)',
                'type' => 'string',
                'rules' => ['nullable', 'string'],
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
                'description' => 'Human-readable result of the resource sync',
                'type' => 'string',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'resources',
                'description' => 'The synced resources (label, uri_key, model)',
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
        /** @var string|null $filterString */
        $filterString = $inputs['filter'] ?? null;
        /** @var string|null $excludeString */
        $excludeString = $inputs['exclude'] ?? null;

        /** @var array<int, string> $filters */
        $filters = $filterString ? array_filter(explode(',', $filterString)) : [];
        /** @var array<int, string> $excludes */
        $excludes = $excludeString ? array_filter(explode(',', $excludeString)) : [];

        // Use Nova::serving to ensure all resources are loaded
        Nova::serving(function (ServingNova $servingNova) use ($filters, $excludes): void {
            $this->processResources($filters, $excludes);
        });

        // Trigger ServingNova event to load resources
        $request = Request::create('/');
        ServingNova::dispatch($this->application, $request);

        if ($this->discoveredResources === []) {
            return $this->fail('No Nova resources found or no resources match the filter criteria.');
        }

        return $this->succeed([
            'message' => 'Successfully synced ' . count($this->discoveredResources) . ' resources to config/nova-authorization.php',
            'resources' => array_map(function (string $resource): array {
                return [
                    'label' => $resource::label(),
                    'uri_key' => $resource::uriKey(),
                    'model' => $resource::$model,
                ];
            }, $this->discoveredResources),
        ]);
    }

    /**
     * Process and sync resources to config
     *
     * @param  array<int, string>  $filters
     * @param  array<int, string>  $excludes
     */
    private function processResources(array $filters, array $excludes): void
    {
        $resources = Nova::$resources;

        if ($resources === []) {
            return;
        }

        // Filter resources if needed
        $filteredResources = $this->filterResources($resources, $filters, $excludes);

        if ($filteredResources === []) {
            return;
        }

        // Update config file
        $this->updateConfigFile($filteredResources);

        $this->discoveredResources = $filteredResources;
    }

    /**
     * Filter resources based on provided options
     *
     * @param  array<int, class-string>  $resources
     * @param  array<int, string>  $filters
     * @param  array<int, string>  $excludes
     * @return array<int, class-string>
     */
    private function filterResources(array $resources, array $filters, array $excludes): array
    {
        $filtered = $resources;

        // Apply include filters
        if ($filters !== []) {
            $filtered = array_filter($filtered, function (string $resource) use ($filters): bool {
                foreach ($filters as $filter) {
                    if (str_contains($resource, $filter)) {
                        return true;
                    }
                }

                return false;
            });
        }

        // Apply exclude filters
        if ($excludes !== []) {
            $filtered = array_filter($filtered, function (string $resource) use ($excludes): bool {
                foreach ($excludes as $exclude) {
                    if (str_contains($resource, $exclude)) {
                        return false;
                    }
                }

                return true;
            });
        }

        return array_values($filtered);
    }

    /**
     * Update the config file with discovered resources
     *
     * @param  array<int, class-string>  $resources
     */
    private function updateConfigFile(array $resources): void
    {
        $configPath = config_path('nova-authorization.php');

        if (! File::exists($configPath)) {
            return;
        }

        // Read existing config file content
        $content = File::get($configPath);

        // Build the resources array string
        $resourcesArray = $this->buildResourcesArray($resources);

        // Replace the resources array in the content
        $pattern = '/[\'"]resources[\'"]\s*=>\s*\[[^\]]*\]/s';
        $replacement = "'resources' => [\n{$resourcesArray}    ]";

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent === null) {
            return;
        }

        // Write back to file
        File::put($configPath, $newContent);
    }

    /**
     * Build the resources array string
     *
     * @param  array<int, class-string>  $resources
     */
    private function buildResourcesArray(array $resources): string
    {
        $resourcesContent = '';
        foreach ($resources as $resource) {
            $resourcesContent .= "        \\{$resource}::class,\n";
        }

        return $resourcesContent;
    }
}
