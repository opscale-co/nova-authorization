<?php

namespace Opscale\NovaAuthorization\Services\Actions;

use Illuminate\Support\Facades\File;
use Opscale\Actions\Action;

final class ModifyPermissionMigration extends Action
{
    final public function identifier(): string
    {
        return 'modify-permission-migration';
    }

    final public function name(): string
    {
        return 'Modify Permission Migration';
    }

    final public function description(): string
    {
        return 'Modify Spatie Permission migration to use ULIDs instead of auto-incrementing IDs';
    }

    /**
     * @return array<int, array{name: string, description: string, type: string, rules: array<string>}>
     */
    final public function parameters(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{success: bool, message: string}
     */
    final public function handle(array $attributes = []): array
    {
        $migrationPath = $this->findPermissionMigration();

        if (! $migrationPath) {
            return [
                'success' => false,
                'message' => 'Permission migration file not found. Please run vendor:publish first.',
            ];
        }

        $content = File::get($migrationPath);

        // Check if already modified
        if (str_contains($content, "->ulid('id')")) {
            return [
                'success' => true,
                'message' => 'Permission migration already modified to use ULIDs.',
            ];
        }

        // Apply modifications
        $modifiedContent = $this->applyUlidModifications($content);

        // Write back to file
        File::put($migrationPath, $modifiedContent);

        return [
            'success' => true,
            'message' => 'Permission migration successfully modified to use ULIDs.',
        ];
    }

    /**
     * Find the permission migration file
     */
    private function findPermissionMigration(): ?string
    {
        $migrationDir = base_path('vendor/orchestra/testbench-core/laravel/database/migrations');

        if (! File::isDirectory($migrationDir)) {
            return null;
        }

        $files = File::files($migrationDir);

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'create_permission_tables')) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Apply ULID modifications to the migration content
     */
    private function applyUlidModifications(string $content): string
    {
        // Replace bigIncrements('id') with ulid('id')->primary() in permissions table
        $content = preg_replace(
            '/(\$table->)bigIncrements\(\'id\'\);(\s*\/\/ permission id)/',
            '$1ulid(\'id\')->primary();$2',
            $content
        );

        // Replace bigIncrements('id') with ulid('id')->primary() in roles table
        $content = preg_replace(
            '/(\$table->)bigIncrements\(\'id\'\);(\s*\/\/ role id)/',
            '$1ulid(\'id\')->primary();$2',
            $content
        );

        // Replace unsignedBigInteger pivot keys with ulid
        // For permission pivot key
        $content = preg_replace(
            '/(\$table->)unsignedBigInteger\(\$pivotPermission\);/',
            '$1ulid($pivotPermission);',
            $content
        );

        // For role pivot key
        $content = preg_replace(
            '/(\$table->)unsignedBigInteger\(\$pivotRole\);/',
            '$1ulid($pivotRole);',
            $content
        );

        return $content;
    }
}
