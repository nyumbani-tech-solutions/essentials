---
name: nyumbani-defaults
description: "Invoke when a Nyumbani app behaves unexpectedly because of an app-wide default (lazy loading throws, migrate:fresh refuses, JSON resources are not wrapped, the debugbar, the log context) or when switching one off in config/essentials.php. Skip for the discord log channel itself (see nyumbani-logging)."
metadata:
  author: nyumbani
---

# Nyumbani App Defaults

Essentials applies these on boot, so an app does not repeat them in `AppServiceProvider`. Each is a key under `defaults` in `config/essentials.php`; set it to `false` to switch it off.

| Key | Effect |
| --- | --- |
| `prevent_lazy_loading` | A missed eager-load throws `LazyLoadingViolationException` outside production, so N+1 queries surface in development and tests. Eager-load what you access. |
| `prohibit_destructive_commands` | `migrate:fresh`, `db:wipe` and friends refuse to run in production. |
| `unwrapped_json_resources` | API resources return their data without the `data` wrapper (`JsonResource::withoutWrapping()`). |
| `debugbar` | The debugbar is enabled locally when it is installed. |
| `context` | The signed-in user, their organization and the request payload join every log entry of a web request (the `AddContext` middleware, pushed on the `web` group only). |

Passwords, `password_confirmation`, `current_password` and `_token` are never logged, and uploaded files are skipped.

## Also applied by Essentials
- The default password rules (`Password::min(8)`) and the login throttle (`essentials.fortify.max_attempts`, 5 a minute for each username and IP address).
- The `alert()` redirect macro and the `period()` request macro (see nyumbani-helpers).
- The Blade directives `@money`, `@currency`, `@numeric`, `@percentage`, `@prettify`, `@date`, `@time`, `@datetime`, `@dateforhumans` and `@bag`.
- The Fortify setup (see nyumbani-auth) and the `discord` log driver (see nyumbani-logging).

The error pages are not an Essentials default any more: they belong to `nyumbani/ui` (`ui.error_pages`, see nyumbani-error-pages).

## Switching one off
```php
// config/essentials.php
'defaults' => [
    'prevent_lazy_loading' => false,
],
```
Switch a default off only for a reason you can write in the pull request. Prefer fixing the query: a lazy-loading exception is a real N+1.

## When something looks wrong
| You see | Because | Do |
| --- | --- | --- |
| `LazyLoadingViolationException` | `prevent_lazy_loading` | `with()` the relation. |
| `migrate:fresh` refuses | `prohibit_destructive_commands` in production | Run it locally, never in production. |
| An API response has no `data` key | `unwrapped_json_resources` | Read the fields at the top level. |
| A log entry carries the user | `context` | Expected. |
