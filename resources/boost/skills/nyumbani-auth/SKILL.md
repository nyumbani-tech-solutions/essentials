---
name: nyumbani-auth
description: "Invoke when working on sign-in, logout, password reset, the login throttle, where a user lands after signing in (HasHomeRoute), or impersonating a user in a Nyumbani app. Skip for roles and permissions (see nyumbani-permissions) and for registration, which Nyumbani apps do not have."
metadata:
  author: nyumbani
---

# Nyumbani Authentication

Essentials configures Laravel Fortify for every app. Never build a login, forgot-password or reset-password page, and never redefine the Fortify routes.

## What is on
Sign-in, logout (always back to `login`), forgot and reset password. Registration, e-mail verification, two-factor authentication and passkeys are off: an administrator creates users. Passwords need at least 8 characters.

## Where a user lands
The user model implements `Nyumbani\Essentials\Contracts\HasHomeRoute`. `homeRoute()` always returns a URL, chosen by permission or role. A user with no access gets the unauthorized page or a 403, never `null`.
```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Nyumbani\Essentials\Contracts\HasHomeRoute;

class User extends Authenticatable implements HasHomeRoute
{
    public function homeRoute(): string
    {
        return match (true) {
            $this->hasPermissionTo('view-dashboard') => route('dashboard'),
            default => route('unauthorized'),
        };
    }
}
```
After signing in a user goes to the page they were trying to reach, or else to `homeRoute()`. The sidebar logo links there too. Outside production, a user model without the contract throws a `LogicException` on sign-in; in production it logs a warning and falls back to `fortify.home`.

## The throttle and the username
`essentials.fortify.max_attempts` (5) is the sign-in attempts a minute for each username and IP address. The form follows `config('fortify.username')`: set it to `phone_number` and the field, label and input type change with it.

## Swapping the sign-in pages
Fortify renders its pages through `Nyumbani\Essentials\Contracts\AuthViews` (`loginView()`, `forgotPasswordView()`, `resetPasswordView(Request)`). Essentials binds its own by default; a theme or the app binds its own implementation in a service provider's `register()` to replace them. With nothing bound, Fortify has no views.
```php
use App\Views\BrandedAuthViews;
use Nyumbani\Essentials\Contracts\AuthViews;

$this->app->singleton(AuthViews::class, BrandedAuthViews::class);
```

## Roles and permissions on the user model
Use `Nyumbani\Essentials\Traits\HasDefaultPermissions` (it brings Spatie's roles and `assignDefaultPermissions()`). Do not import `Spatie\Permission\Traits\HasPermissions` by mistake: it has no roles and no default permissions. `nyumbani:install` flags the wrong one in its checklist.

## Impersonation
Add `Lab404\Impersonate\Models\Impersonate` to the user model. While a user is impersonated, the portal layout shows a bar under the page header with a Leave button that posts to `essentials.impersonation.leave`, which signs back in as the original user and sends them to their home route. Decide who may impersonate with a policy (`Gate::authorize('impersonate', $user)`), then call `user()->impersonate($user)`.

## Routes you can rely on
`login`, `login.store`, `logout`, `password.request`, `password.email`, `password.reset`, `password.update` and `essentials.impersonation.leave`. The portal layout sends a guest to `login` as a safety net; protect routes with the `auth` middleware anyway.
