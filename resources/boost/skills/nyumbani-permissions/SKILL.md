---
name: nyumbani-permissions
description: "Invoke when adding or changing roles and permissions in a Nyumbani app: database/seeders/data/permissions.json, php artisan nyumbani:permissions, assigning a role to a user (assignDefaultPermissions), checking a permission in a policy, the role list for a select box, or the permission tables' group column. Skip for sign-in and impersonation (see nyumbani-auth)."
metadata:
  author: nyumbani
---

# Nyumbani Roles and Permissions

Roles and their permissions are written once in `database/seeders/data/permissions.json`, grouped by area, and synced into Spatie's tables with one command. The file is the only source of truth: never create a role or permission by hand in a seeder, a controller or tinker.

## The file
```json
{
    "admin": {
        "invoices": ["view-invoices", "create-invoice"],
        "users": ["view-users", "create-user"]
    },
    "clerk": {
        "invoices": ["view-invoices"]
    }
}
```
Role, then group, then permissions. Name a permission as a kebab-case verb-noun: plural for a list (`view-invoices`), singular for one record (`create-invoice`). `nyumbani:install` creates a starter file when the app has none.

## Sync it
`php artisan nyumbani:permissions` (no options) creates the roles and permissions, fills each permission's `group`, gives new permissions to users who already hold the role, and deletes permissions removed from the file. Run it on every deploy; `nyumbani:install` runs it once the tables exist.

| You change the file by | The sync |
| --- | --- |
| Adding a permission to a role | Creates it and gives it to every user who holds that role. |
| Removing a permission | Deletes it everywhere. |
| Adding a role | Creates the role; users are not moved into it. |
| Moving a permission to another group | Updates its group. |

The tables need the `group` column. `nyumbani:install` publishes a migration that creates them with it, or a small one that adds it to tables an app created from Spatie's own migration. Run `php artisan migrate` after the installer.

## On the models
Use `Nyumbani\Essentials\Traits\HasDefaultPermissions` on the user model, never `Spatie\Permission\Traits\HasPermissions`, which has no roles and no default permissions (the installer checklist flags the mix-up).
```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Nyumbani\Essentials\Traits\HasDefaultPermissions;

class User extends Authenticatable
{
    use HasDefaultPermissions;
}

$user->assignDefaultPermissions('clerk');   // the role, with exactly its permissions from the file
User::defaultPermissionsFor('clerk');       // the permission names of that role
```
Give the app's `Role` model `Nyumbani\Essentials\Traits\HasRoleList`; `Role::list()` returns the role names from the file, for a select box.

## Check permissions, never roles
```php
use App\Models\User;

public function viewAny(User $user): bool
{
    return $user->hasPermissionTo('view-invoices');
}
```
Policies ask for the permission. The user model's `homeRoute()` (see nyumbani-auth) may choose by permission too, and a user with no access lands on the unauthorized page.

## Do not
- Edit permissions in the database or a seeder: change the file and sync.
- Check `hasRole()` in a policy or a view.
- Seed users with a role the file does not define.
- Call `Gate::define` for a permission the file already holds.

## Tests
Run `$this->artisan('nyumbani:permissions')` in the test (the file is the fixture), then `User::factory()->create()->assignDefaultPermissions('admin')`.
