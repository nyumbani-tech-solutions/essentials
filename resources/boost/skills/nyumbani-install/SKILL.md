---
name: nyumbani-install
description: "Invoke when setting up a Nyumbani app, running or changing php artisan nyumbani:install, reading its setup checklist, or fixing a checklist item: rules, Boost, config files, migrations, imports, the discord log channel, the HasDefaultPermissions trait, the UI package's own installer (`ui:install`, `--portal`), or the Pint step. Skip for using a feature once it is installed."
metadata:
  author: nyumbani
---

# Nyumbani Install

`php artisan nyumbani:install` is the one command that sets up and refreshes a Nyumbani app. It is safe to run again. On an interactive run it first shows a checkbox, `Which Nyumbani packages does this app use?` (Daraja, Messaging, Data tables, UI and Docs; the ones the app already requires are ticked, and Docs by default), then asks Boost's first-time questions and one optional one of its own, defaulting to no: `Run the migrations now?`. When UI is chosen it runs the UI package's own installer, `php artisan ui:install`, which asks `Set up a basic portal?` (default no). With `--no-interaction` it never asks and manages only the optional packages the app already requires, never installing a new one. `--with=daraja,messaging,dt-tables` and `--without=messaging` (short or full names, comma separated or repeated) decide without the checkbox; a package left out gets no config, env keys, rule or Boost entry. A left-out package the app already requires is kept, unless the developer confirms `Remove nyumbani/x from this app?` (default no, interactive only) or passes `--remove`: then the installer runs `composer remove`, drops the package and its skills from `boost.json`, tells the developer to delete the published config file, and finishes in a fresh process. Never remove without one of those. An app that must never use a package (Nyumbani Connect never uses `nyumbani/messaging`) runs it with `--without=messaging`. Composer runs it on `post-update-cmd` as `nyumbani:install --no-interaction --ansi`, so a `composer update` brings the newest rules and skills.

## Options
| Option | Meaning |
| --- | --- |
| `--portal` | Passed to `ui:install` when the UI package is chosen: set up the basic portal without asking. |
| `--with=a,b` | Install the named optional packages (`daraja`, `messaging`, `dt-tables`, `ui`, `docs`, or the full `nyumbani/...` name; comma separated or repeated) without the checkbox. Packages the app already requires stay chosen. |
| `--without=a,b` | Leave the named packages out without the checkbox: no config, env keys, rule, checklist item or Boost entry. Wins over `--with` and over a package already required, which is kept unless removed. |
| `--remove` | `composer remove` each optional package the app requires and left out, without asking, and drop it and its skills from `boost.json`. Without it an interactive run asks per package (default no) and a non-interactive run removes nothing. |
| `-n`, `--no-interaction` | Never ask; choose only the packages the app already requires. |

Choice rule: no `--with` or `--without` on an interactive run shows the checkbox; otherwise the chosen packages are the ones already required, plus `--with`, minus `--without`. Examples: `nyumbani:install --with=dt-tables --without=messaging,daraja` (Connect), `nyumbani:install --without=messaging --remove`, `nyumbani:install --no-interaction --portal --with=daraja,messaging,dt-tables,docs`. `docs` is always installed with `composer require --dev` (a copy in `require` is moved by the same command, and the checklist flags it), never listed in `boost.json`, and every package boots without it. `php artisan nyumbani:permissions` has no options. When a change to the options is made, update this table, the Command options docs section and the tests.

## What it does, in order
1. **Dependencies.** Installs `laravel/boost --dev` if it is missing, and requires the optional packages chosen (`nyumbani/daraja`, `nyumbani/messaging`, `nyumbani/dt-tables`) in the app's own `composer.json` when the app does not already. Essentials itself requires none of them. Boost reads guidelines and skills only from packages the app requires itself, so indirect ones are invisible to it. Then it runs itself again in a fresh process, because the new classes are not loaded in the first.
2. **Rules.** Copies the Nyumbani rules to `.ai/rules/nyumbani-*.md`, replaces older copies, removes ones the package no longer ships, and rebuilds `.ai/rules/index.md`. Never edit them; the next run overwrites them.
3. **Config and migrations.** Publishes the `essentials`, `permission` and `activitylog` config files, and `daraja`, `messaging`, `dt-tables` and `ui` for the chosen packages, if absent (Essentials registers the `dt-tables-config` tag, since the data tables package has none). Everything the installer puts in the app from the package goes through `vendor:publish` tags: the configs, the migrations (`essentials-permissions-migration`, `essentials-permissions-group-migration`, `activitylog-migrations`) and the starter `permissions.json` (`essentials-permissions`), so each can also be published by hand with `php artisan vendor:publish --tag=...`, and the activity log migration. The permission tables come from Essentials' own migration, which already has the `group` column; an app that published Spatie's migration earlier gets a small `update_permissions_table_add_group_column` migration instead. It never publishes a migration the app already has.
4. **App files, only what is missing.** Adds blank package keys to `.env.example` (never `.env`), the `discord` log channel to `config/logging.php`, and a starter `database/seeders/data/permissions.json`. It never edits `vite.config.js`, `app.css` or `app.js`: the front end belongs to the UI package.
5. **UI (only when chosen).** Runs `php artisan ui:install`, passing `--portal` along. That command adds the shared Vite aliases to `vite.config.js` (only the missing ones, checked with Node before the file is kept), imports the theme in `resources/css/app.css` (by the `@vendor` alias when the config has it) with the `@source` lines for `app/Enums`, `app/DataTables` and `config/dt-tables.php`, imports `ui.js` and the Essentials `helpers.js` in `resources/js/app.js` and starts Livewire there, gives an untouched `config/dt-tables.php` the Tailwind theme and the Nyumbani classes, optionally publishes the portal (`ui-portal` tag: a dashboard, a layout and a sidebar, never overwriting a view the app has), runs `npm run build` when the app has `node_modules`, and prints its own UI checklist. A route for the dashboard stays a note, since application code is never edited.
6. **Rules for the UI.** The view, JS and Livewire rules (`views.md`, `js.md`, `livewire.md`) are copied only when the UI package is chosen.
7. **Migrations (optional).** On an interactive run, when the permission and activity tables are missing, asks `Run the migrations now?` (default no). A yes runs `php artisan migrate`, so the roles and permissions sync in the same run; a non-interactive run never asks and never migrates.
8. **Roles and permissions.** Syncs the roles and permissions from `permissions.json` with `php artisan nyumbani:permissions`, only once the permission tables exist. On a first run where the migrations did not run (you answered no, or it was non-interactive) it says so and waits for the next run.
9. **Storage link.** Links `public/storage` when a configured link is missing.
10. **Pint.** Runs `vendor/bin/pint` on only the PHP files the installer published or edited (configs, migrations, `config/logging.php`), never the app's own code; skipped when the app has no Pint.
11. **Boost.** The first time (no `boost.json`) it runs `boost:install`. Always, it loads Essentials and the chosen packages into `boost.json`, lists their skills there (`boost:update` installs only the skills named in `boost.json`, and finds new ones only by asking, which a non-interactive run cannot), sets the agent to `claude_code` when none is set (a non-interactive first install picks none, and `boost:update` refuses to run without one), then runs `boost:update`.
12. **Checklist.** Prints a progress bar and, for each item, a ✓ (done, quiet), ✗ (to do, with its fix) or ○ (optional, with its fix) for Essentials; the UI prints its own.
13. **Docs invitation.** Ends with a panel linking the UI Quick start and the docs of Essentials, UI, Daraja, Messaging and DtTables (built from the registered docs, so the links always match the real routes).

## Reading the UI checklist
These items are printed by `php artisan ui:install`, which is safe to run again.

| Item | Fix when it is not done |
| --- | --- |
| Theme imported | Add `@import '../../vendor/nyumbani/ui/resources/css/theme.css';` in place of the Tailwind import in `app.css`. |
| Enums, data tables and their config scanned | Add the `@source` lines the installer names to `app.css`. |
| Vite aliases | Add the aliases to `resolve.alias` in `vite.config.js`, or let the installer do it. |
| UI scripts and formatting helpers imported | Add the two `import` lines to `app.js`, before Livewire starts. |
| Livewire started | Import `Livewire` from `../../vendor/livewire/livewire/dist/livewire.esm` and call `Livewire.start();`. Without it Alpine never starts and the modals do nothing. |
| Basic portal | Optional: run `php artisan ui:install --portal`. |

## Reading the checklist
| Item | Fix when it is not done |
| --- | --- |
| User model implements `HasHomeRoute` | Implement `Nyumbani\Essentials\Contracts\HasHomeRoute` and return a URL from `homeRoute()`. A dashboard route is not a checklist requirement, with or without the portal; after `--portal` the installer only prints a note about the route while the user model or a GET route in `auth` named `dashboard` or `home` is missing. |
| User model has roles and default permissions | Use `Nyumbani\Essentials\Traits\HasDefaultPermissions`. **Not** `Spatie\Permission\Traits\HasPermissions`: it has no roles and no `assignDefaultPermissions()`. |
| User model can be impersonated | Use `Lab404\Impersonate\Models\Impersonate`. |
| Migrations have run | Run `php artisan migrate`, or answer yes to `Run the migrations now?` on the next interactive run. The installer publishes migrations and runs them only when you say yes. |
| Laravel Boost is installed | Run the installer; it requires Boost for you. |
| Boost is set up with the Nyumbani packages | Run `composer require` for the packages you chose, then the installer again. |
| discord log channel | Optional: add a `discord` channel to `config/logging.php` (the installer does when the file allows). |
| Docs package is a dev dependency | Optional, shown when Docs was chosen: `composer require --dev nyumbani/docs`. |
| Daraja credentials, Connect settings | Optional, shown only when that package was chosen: set the `DARAJA_*` keys, or `NYUMBANI_CONNECT_BASE_URL` and `NYUMBANI_CONNECT_TOKEN`, in `.env`. |

The installer reports; it never edits the user model, the routes or any other application code. It only adds the configuration, build and framework files described above, and the portal's files (through `ui:install`), none of which it overwrites.

## Rules for changing the installer
- Every step must be idempotent: add only what is missing, and leave anything the app already has.
- Do not run `migrate` unless the developer answers yes to the interactive question (never in a non-interactive run), do not run `composer` for anything but the dependencies above, and never touch `.env`.
- A step that edits a file must still leave valid PHP (the logging step checks this before saving).
- When a step is added or changed, update the Installation docs table, this skill, the checklist in `Console/Support/InstallChecklist.php` and the tests in `InstallCommandTest.php`. A change to a front-end step belongs to the UI package: its installer (`ui:install`), `UiChecklist`, its docs and `UiInstallCommandTest.php`. The tests fake `vendor:publish`, `storage:link` and Boost's commands so nothing writes into the real project.
