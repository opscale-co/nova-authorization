<?php

namespace Opscale\NovaAuthorization;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Opscale\NovaAuthorization\Nova\Permission;
use Opscale\NovaAuthorization\Nova\Role;
use Opscale\NovaAuthorization\Policies\Policy;
use Opscale\NovaAuthorization\Services\Actions\AssignRole;
use Opscale\NovaAuthorization\Services\Actions\ClearCache;
use Opscale\NovaAuthorization\Services\Actions\ConfigurePermissions;
use Opscale\NovaAuthorization\Services\Actions\CreatePermissions;
use Opscale\NovaAuthorization\Services\Actions\CreateRole;
use Opscale\NovaAuthorization\Services\Actions\ModifyPermissionMigration;
use Opscale\NovaAuthorization\Services\Actions\SyncResources;
use Opscale\NovaPackageTools\NovaPackage;
use Opscale\NovaPackageTools\NovaPackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class ToolServiceProvider extends NovaPackageServiceProvider
{
    /**
     * @phpstan-ignore solid.ocp.conditionalOverride
     */
    public function configurePackage(Package $package): void
    {
        /** @var NovaPackage $package */
        $package
            ->name('nova-authorization')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands([
                AssignRole::class,
                ClearCache::class,
                ConfigurePermissions::class,
                CreatePermissions::class,
                CreateRole::class,
                ModifyPermissionMigration::class,
                SyncResources::class,
            ])
            /** @phpstan-ignore argument.type */
            ->hasResources([
                Permission::class,
                Role::class,
            ])
            ->hasInstallCommand(function (InstallCommand $installCommand): void {
                $installCommand
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('opscale-co/nova-authorization');
            });
    }

    /**
     * @phpstan-ignore solid.ocp.conditionalOverride
     */
    public function packageBooted(): void
    {
        parent::packageBooted();
        $this->registerEventListeners();

        Nova::serving(function (): void {
            $this->registerPolicies();
        });
    }

    final protected function registerPolicies(): void
    {
        /** @var array<class-string> $resources */
        $resources = Config::get('nova-authorization.resources', []);
        /** @var array<string, class-string> $predefinedPolicies */
        $predefinedPolicies = Config::get('nova-authorization.policies', []);

        foreach ($resources as $resource) {
            $policy = $predefinedPolicies[$resource] ??
                $this->generatePolicyClass($resource);
            Gate::policy($resource::$model, $policy);
        }
    }

    /**
     * @return class-string
     */
    final protected function generatePolicyClass(string $resource): string
    {
        $class = get_class(new class extends Policy
        {
            protected ?string $resource = null;

            public function getResource(): string
            {
                return $this->resource ?? '';
            }

            public function setResource(string $resource): void
            {
                $this->resource = $resource;
            }
        });

        $binding = $resource::uriKey() . '-policy';
        $this->app->bind($binding, function ($app) use ($class, $resource): object {
            $instance = new $class;
            $instance->setResource($resource::singularLabel());

            return $instance;
        });

        /** @var class-string */
        return $binding;
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            RoleAttached::class,
            ClearCache::class
        );

        Event::listen(
            RoleDetached::class,
            ClearCache::class
        );
    }
}
