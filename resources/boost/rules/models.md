---
paths:
  - 'app/Models/**'
---

# Models

## Use $fillable allow-lists
Declare `protected $fillable` with the exact assignable columns. Never use `$guarded`.

## Dual-key strategy
Auto-increment `id` is the internal key; a unique `ulid` column is the public route key through `Nyumbani\Essentials\Traits\HasUlids` (not Laravel's built-in trait). Every model whose table has a `ulid` column uses it; pivot tables without one are exempt. Never read `$model->ulid` in views, controllers or requests; use `$model->getRouteKey()`. Never add `ulid` to `$fillable`: the trait fills it on create.

## Document every property
`$fillable`, `$casts`, `$timestamps` and any other declared property carry a docblock with a one-line description only, no `@var` tag.

## Member order
`boot()` / `booted()`, `casts()` and `$fillable` first, then the Attribute accessors as one block, then relationships, then other methods.

## Accessors are get-only
Define computed values with `Attribute::make(get: ...)`. Never pass a `set:` closure; transformation on write belongs in a cast class or a `casts()` type.

## Define a relationship only when it is needed
Add a relationship on a model only when something uses it; do not define the inverse side by default. Relations must match real columns: when a table has no tenant column, reach the tenant with `hasManyThrough` / `hasOneThrough`. Pivot relations declare `withPivot()` for extra columns and `withTimestamps()` when present.

## Tenant data goes through the tenant relation
Query tenant-owned data from the tenant's relation, e.g. `$tenant->services()->latest('id')->get()`, not `Service::whereBelongsTo($tenant)`. Never list another tenant's rows.

## The User model knows its home
The `User` model implements `Nyumbani\Essentials\Contracts\HasHomeRoute`. `homeRoute()` always returns a URL, chosen by role or permission; a user with no access gets the unauthorized page or an abort with 403, never `null`.

## The User model takes HasDefaultPermissions
Give the `User` model `Nyumbani\Essentials\Traits\HasDefaultPermissions`, never `Spatie\Permission\Traits\HasPermissions`: the Spatie one has no roles and no `assignDefaultPermissions()`.

## Local scopes use #[Scope]
Define scopes with the `#[Scope]` attribute on a protected method, never `scopeXxx()` names.

## Lifecycle hooks in booted()
Use `booted()` closures or trait boot methods, not observer classes or `#[ObservedBy]`. Keep each closure short: put the work in a method on the model and call it from the closure (`static::saving(fn (self $model) => $model->setNumber())`).

## No model-level $with
Never set `$with`. Lazy loading is prevented outside production, so eager-load explicitly per query.

## Morph map
Register every morphable model in `Relation::enforceMorphMap()` in `AppServiceProvider`, with a kebab-case key.
