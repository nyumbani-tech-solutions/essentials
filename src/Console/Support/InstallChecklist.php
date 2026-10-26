<?php

namespace Nyumbani\Essentials\Console\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Console\InstallCommand as BoostInstallCommand;
use Nyumbani\Essentials\Contracts\HasHomeRoute;
use Spatie\Permission\Traits\HasPermissions as SpatieHasPermissions;
use Throwable;

/**
 * What an app still has to do by hand after nyumbani:install, found by looking and never by editing application code
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class InstallChecklist
{
    /**
     * Create the checklist for the optional packages the app chose, which defaults to the ones it requires itself.
     *
     * @param  list<string>|null  $packages
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function __construct(protected ?array $packages = null)
    {
        //  Fall back to what the app requires
        $this->packages ??= OptionalPackages::required();
    }

    /**
     * Get every check as a list of label, state (pass, fail or optional) and how to fix it
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function items(): array
    {
        //  Find the user model the app signs people in with
        $user = config('auth.providers.users.model');

        return [
            $this->homeRouteItem($user),
            $this->item('User model has roles and default permissions', is_string($user) && method_exists($user, 'assignDefaultPermissions'), $this->permissionsFix($user)),
            $this->item('User model can be impersonated', is_string($user) && method_exists($user, 'canBeImpersonated'), 'Use Lab404\Impersonate\Models\Impersonate on the user model.'),
            $this->item('Migrations have run (permissions with group, activity_log)', $this->hasTables(), 'Run php artisan migrate.'),
            $this->item('Laravel Boost is installed', class_exists(BoostInstallCommand::class), 'Run php artisan nyumbani:install, which requires laravel/boost --dev for you.'),
            $this->item('Boost is set up with the Nyumbani packages', $this->boostLoadsPackages(), $this->boostFix()),
            $this->item('discord log channel', $this->hasDiscordChannel(), "Add 'discord' => ['driver' => 'discord', 'url' => env('DISCORD_WEBHOOK_URL')] to config/logging.php.", optional: true),
            ...(in_array('nyumbani/docs', $this->packages) ? [$this->item('Docs package is a dev dependency', (new DirectRequirements)->missing(['nyumbani/docs']) === [] && (new DirectRequirements)->misplaced(['nyumbani/docs']) === [], 'Run composer require --dev nyumbani/docs, which also moves it out of require if it sits there.', optional: true)] : []),
            ...(in_array('nyumbani/daraja', $this->packages) ? [$this->item('Daraja credentials set', filled(config('daraja.docs.short_code')) && filled(config('daraja.docs.consumer_key')), 'Set DARAJA_SHORT_CODE, DARAJA_CONSUMER_KEY, DARAJA_CONSUMER_SECRET and DARAJA_PASS_KEY in .env.', optional: true)] : []),
            ...(in_array('nyumbani/messaging', $this->packages) ? [$this->item('Nyumbani Connect settings set', filled(config('messaging.connect.base_url')) && filled(config('messaging.connect.access_token')), 'Set NYUMBANI_CONNECT_BASE_URL and NYUMBANI_CONNECT_TOKEN in .env.', optional: true)] : []),
        ];
    }

    /**
     * Get the home route check: the user model implements HasHomeRoute, which is all it asks, so a dashboard route is not a requirement
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function homeRouteItem(mixed $user): array
    {
        return $this->item('User model implements HasHomeRoute', is_a($user, HasHomeRoute::class, true), 'Implement Nyumbani\Essentials\Contracts\HasHomeRoute and add homeRoute().');
    }

    /**
     * Determine whether the app has the discord log channel, read from config/logging.php as well as the loaded config, which still lacks a channel the installer added moments ago
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function hasDiscordChannel(): bool
    {
        return config('logging.channels.discord.driver') === 'discord'
            || (File::exists(config_path('logging.php')) && preg_match('/[\'"]discord[\'"]\s*=>/', File::get(config_path('logging.php'))) === 1);
    }

    /**
     * Determine whether the user model implements HasHomeRoute and a route shows the portal's dashboard, which is what the home route checklist item needs
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function hasHomeRoute(): bool
    {
        return is_a(config('auth.providers.users.model'), HasHomeRoute::class, true) && $this->hasDashboardRoute();
    }

    /**
     * Determine whether a GET route inside the auth middleware is the dashboard, found by its name (dashboard or home, with any prefix) or by showing the portal's dashboard view, wherever it is defined and whatever runs it
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function hasDashboardRoute(): bool
    {
        return collect(Route::getRoutes()->getRoutes())->contains(fn ($route) => in_array('GET', $route->methods())
            && collect($route->gatherMiddleware())->contains(fn ($middleware) => is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:')))
            && (($route->defaults['view'] ?? null) === 'admins.pages.dashboard' || preg_match('/(^|\.)(dashboard|home)$/', (string) $route->getName()) === 1));
    }

    /**
     * Build one check
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function item(string $label, bool $passes, string $fix, bool $optional = false): array
    {
        return ['label' => $label, 'state' => $passes ? 'pass' : ($optional ? 'optional' : 'fail'), 'fix' => $fix];
    }

    /**
     * Determine whether a file exists and mentions the text
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function fileHas(string $path, string $text): bool
    {
        return File::exists($path) && str_contains(File::get($path), $text);
    }

    /**
     * Say how to give the user model the Nyumbani permissions trait, naming the Spatie trait of the same name when that is what it imports
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function permissionsFix(mixed $user): string
    {
        //  Spatie's HasPermissions is the easy one to import by mistake, and has no roles and no default permissions
        if (is_string($user) && class_exists($user) && in_array(SpatieHasPermissions::class, class_uses_recursive($user), true)) {
            return 'The user model imports Spatie\Permission\Traits\HasPermissions. Import Nyumbani\Essentials\Traits\HasDefaultPermissions instead, which brings roles and assignDefaultPermissions().';
        }

        return 'Use Nyumbani\Essentials\Traits\HasDefaultPermissions on the user model.';
    }

    /**
     * Say how to get Boost to load the Nyumbani packages, whose usual blocker is that Boost reads only the packages an app requires itself
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function boostFix(): string
    {
        //  Boost ignores guidelines and skills of packages that reach the app only through another package
        $missing = (new DirectRequirements)->missing(array_diff($this->packages, OptionalPackages::dev($this->packages)));

        if ($missing !== []) {
            return 'Boost only loads packages the app requires itself. Run composer require '.implode(' ', $missing).', then php artisan nyumbani:install.';
        }

        return 'Run php artisan nyumbani:install to set Boost up and load Essentials and the packages you chose.';
    }

    /**
     * Determine whether Boost has its settings and loads the Nyumbani packages, so their guidelines and skills reach the agents
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function boostLoadsPackages(): bool
    {
        //  Boost writes its settings the first time it is installed
        $path = base_path('boost.json');

        if (! File::exists($path)) {
            return false;
        }

        //  Read the packages Boost loads from
        $packages = json_decode(File::get($path), true)['packages'] ?? [];

        //  Essentials and every package the app chose must be among them
        return array_diff(['nyumbani/essentials', ...array_diff($this->packages, OptionalPackages::dev($this->packages))], $packages) === [];
    }

    /**
     * Determine whether the permission and activity tables exist, with the permission group
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function hasTables(): bool
    {
        try {
            $permissions = config('permission.table_names.permissions', 'permissions');

            return Schema::hasTable($permissions)
                && Schema::hasColumn($permissions, 'group')
                && Schema::hasTable(config('activitylog.table_name', 'activity_log'));
        } catch (Throwable) {
            return false;
        }
    }
}
