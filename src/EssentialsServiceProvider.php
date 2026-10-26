<?php

namespace Nyumbani\Essentials;

use Illuminate\Support\ServiceProvider;
use Nyumbani\Docs\DocsRegistry;
use Nyumbani\DtTables\DtTablesServiceProvider;
use Nyumbani\Essentials\Console\Commands\InstallCommand;
use Nyumbani\Essentials\Console\Commands\PermissionsCommand;
use Nyumbani\Essentials\Docs\EssentialsDocs;
use Nyumbani\Essentials\Providers\DefaultsServiceProvider;
use Nyumbani\Essentials\Providers\FortifyServiceProvider;
use Nyumbani\Essentials\Providers\LoggingServiceProvider;
use Nyumbani\Essentials\Providers\MacroServiceProvider;
use Nyumbani\Essentials\Providers\ViewServiceProvider;

class EssentialsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function register(): void
    {
        //  Load helpers
        $this->loadHelpersFrom(__DIR__.'/Utils');

        //  Load the configuration file
        $this->mergeConfigFrom(essentials_path('config/essentials.php'), 'essentials');

        //  Register the Blade directives
        $this->app->register(ViewServiceProvider::class);

        //  Register the redirect macros
        $this->app->register(MacroServiceProvider::class);

        //  Register the app-wide defaults
        $this->app->register(DefaultsServiceProvider::class);

        //  Register the Fortify defaults
        $this->app->register(FortifyServiceProvider::class);

        //  Register the discord log driver
        $this->app->register(LoggingServiceProvider::class);
    }

    /**
     * Bootstrap the application services.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function boot(): void
    {
        //  Load the views
        $this->loadViewsFrom(essentials_path('resources/views'), 'essentials');

        //  Load the translations
        $this->loadTranslationsFrom(essentials_path('lang'), 'essentials');

        //  Add the docs of Essentials, when the docs package is installed
        if (class_exists(DocsRegistry::class)) {
            $this->app->make(DocsRegistry::class)->register(EssentialsDocs::describe());
        }

        //  Load the routes
        $this->loadRoutesFrom(essentials_path('routes/web.php'));

        //  Publish the configuration file
        $this->publishes([
            essentials_path('config/essentials.php') => config_path('essentials.php'),
        ], 'essentials-config');

        //  Publish the data tables config, which its own package offers no tag for, when the app uses data tables
        if (class_exists(DtTablesServiceProvider::class)) {
            $this->publishes([
                DtTablesServiceProvider::path('config/dt-tables.php') => config_path('dt-tables.php'),
            ], 'dt-tables-config');
        }

        //  Publish the permission tables migration, with the group column built in, and the small one for tables an app created without it, each under a timestamped name
        $stamp = now()->format('Y_m_d_His');

        $this->publishes([
            essentials_path('database/migrations/create_permission_tables.php.stub') => database_path("migrations/{$stamp}_create_permission_tables.php"),
        ], 'essentials-permissions-migration');

        $this->publishes([
            essentials_path('database/migrations/update_permissions_table_add_group_column.php.stub') => database_path("migrations/{$stamp}_update_permissions_table_add_group_column.php"),
        ], 'essentials-permissions-group-migration');

        //  Publish the starter permissions file
        $this->publishes([
            essentials_path('resources/stubs/permissions.json') => database_path('seeders/data/permissions.json'),
        ], 'essentials-permissions');

        //  Register the Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, PermissionsCommand::class]);
        }
    }

    /**
     * Add any package helpers to the application.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function loadHelpersFrom(string $path): void
    {
        foreach (glob("$path/*.php") as $helper) {
            require_once $helper;
        }
    }
}
