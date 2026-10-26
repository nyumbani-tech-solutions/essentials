# nyumbani/essentials

The opinionated foundation of the Nyumbani ecosystem, and headless: it has no screens. It decides how every internal Nyumbani app is structured, so a developer who knows one app can work in any other. The look of an app (layouts, sign-in pages, modal, error pages, theme) is the separate [`nyumbani/ui`](../ui) package, which requires this one.

It covers four areas:

1. Helpers (`src/Utils`): `money()`, `numeric()`, the date helpers and the Blade directives built on them, plus their JavaScript twins in `resources/dist/helpers.js`
2. Service classes (`src/Services`) and traits (`src/Traits`)
3. Packages it standardises and requires: Fortify, Spatie permissions, activity log and impersonation. `nyumbani/ui`, `nyumbani/daraja` (M-Pesa), `nyumbani/messaging` (SMS) and `nyumbani/dt-tables` (data tables) are optional and chosen in the installer
4. AI skills and rules (`resources/boost`)

## Requirements

- PHP 8.3 or newer and Laravel 12 or 13
- A `User` model and a `users` table

## Install

1. Require it:

```
composer require nyumbani/essentials
```

2. Run the installer. It is safe to run again whenever a package changes:

```
php artisan nyumbani:install
```

It asks which optional packages the app uses (UI, Daraja, Messaging, Data tables, Docs), requires the ones you pick, copies the rules, publishes the config files and migrations, adds the missing keys to `.env.example`, the `discord` log channel and a starter `permissions.json`, optionally runs the migrations and syncs the roles and permissions, links storage, formats what it published with Pint, sets up Laravel Boost (requiring `laravel/boost --dev` first if it is missing), and ends with a checklist of what is left for you. When you pick UI it also runs `php artisan ui:install`. Essentials never touches `vite.config.js`, `app.css` or `app.js`.

3. Prepare the user model. The installer never edits application code, so add the contract and the traits yourself:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Lab404\Impersonate\Models\Impersonate;
use Nyumbani\Essentials\Contracts\HasHomeRoute;
use Nyumbani\Essentials\Traits\HasDefaultPermissions;

class User extends Authenticatable implements HasHomeRoute
{
    use HasDefaultPermissions, Impersonate;

    public function homeRoute(): string
    {
        return route('dashboard');
    }
}
```

4. Migrate, sync the roles and check the checklist:

```
php artisan migrate
php artisan nyumbani:install
```

Without `nyumbani/ui`, Fortify has no sign-in pages until the app binds its own `Nyumbani\Essentials\Contracts\AuthViews`.

### Options

| Option | Meaning |
| --- | --- |
| `--portal` | Passed to `ui:install`: set up the basic portal without asking. |
| `--with=ui,dt-tables,docs` | Install the named optional packages without the checkbox. `docs` is installed with `composer require --dev`. |
| `--without=messaging` | Leave the named packages out without the checkbox. |
| `--remove` | Remove the packages left out that the app already requires, without asking. |
| `-n`, `--no-interaction` | Never ask; manage only the optional packages the app already requires. |

A headless app, with no screens, SMS or payments: `php artisan nyumbani:install --with=dt-tables --without=ui,messaging,daraja`.

## Configuration

`nyumbani:install` publishes `config/essentials.php`; to publish it again by hand, run `php artisan vendor:publish --tag=essentials-config`. Every key has a working default; see the Configuration docs page.

## Docs

The full docs are served at `/nyumbani-essentials/docs`, in the `local` environment only, by `nyumbani/docs`, a dev dependency the installer offers (`composer require --dev nyumbani/docs`); without it the app boots as usual and serves no docs. Start with Installation, then Anatomy of a feature.
