---
paths:
  - 'app/**'
---

# App

## House style overrides the generic CLAUDE.md guidance
Where these rules conflict with the generic Laravel Boost text in CLAUDE.md, these rules win: `//  ` comments, an `@author` tag on every method, UPPER_SNAKE_CASE enum cases and the helper-first idioms below.

## Add an @author tag to all methods
Every PHP method gets a PHPDoc block with a one-line description and an `@author` tag. An existing author tag is never overwritten. When no author is defined, use the current developer's identity from `git config user.name` and `git config user.email`, e.g. `@author Jane Doe <jane@example.com>`; if git has none, ask. The PHPDoc holds only the description and the `@author` tag. Do not write `@param` or `@return` tags; the type hints already describe them.

## Never use @param or @return in doc blocks
Doc blocks hold a one-line summary and the `@author` tag only. Do not write `@param` or `@return` tags; the type hints already describe them. Put an array's shape in a `@var` on the property or a short sentence in the summary.

## Comment format
Narrate method bodies with `//  ` comments (double slash followed by two spaces) above each logical step, in both PHP and JS. A method whose body is a single statement needs no inline comment.

## Chain method calls one per line
When a statement chains more than three links, counting the starting call or object, or the chain is too long for one line (roughly over 100 characters), put the start on the first line and each further call on its own line, indented once. This holds for `return` statements too, in PHP and in JavaScript. A short chain of three links or fewer stays on one line. Give the statement its usual `//  ` comment above it.

```php
//  Log the deletion
activity()
    ->performedOn($customer)
    ->event('customer-deleted')
    ->log('Deleted customer '.$customer->name);

//  Go back to the list, which is a short chain that is too long for one line
return redirect()
    ->route('customers.index')
    ->alert('success', trans('alerts.customers.deleted', ['name' => $customer->name]));

//  A short chain stays on one line
$customer->orders()->count();
```

## Keep comments short
Comments say what, in a short phrase. One line is the norm for a doc summary or an inline comment; never write a paragraph. Skip the why-and-how backstory; put a non-obvious reason in a few words, not a sentence chain.

## Import FQCNs at the top
Always `use` classes; never reference a fully-qualified class name inline.

## Naming
Use descriptive full names with no abbreviations. Boolean methods and properties start with `is`, `has`, `can` or `should`. Class members are ordered properties first, then methods.

## Document every class property
Every declared property gets a PHPDoc block with a one-line description only, no `@var` tag. Constructor-promoted properties are documented by the constructor's docblock.

## Every database write runs in a DB::transaction()
Put every create, update and delete in a `DB::transaction()` closure, together with everything written alongside it, such as the activity log entry, so they succeed or fail as one. This holds for a single write too, because its activity log entry is a second write. Never call `beginTransaction()`, `commit()` or `rollBack()` manually.

Keep what cannot be rolled back outside the closure and after it: sending an SMS or email, dispatching jobs and events, calling an HTTP API, and the redirect with its alert. A job that reads the saved rows is dispatched after the transaction, or with `afterCommit()`.

```php
//  Delete the customer and log it as one unit
DB::transaction(function () use ($customer) {
    //  Delete the customer
    $customer->delete();

    //  Log the deletion
    activity()
        ->performedOn($customer)
        ->event('customer-deleted')
        ->log('Deleted customer '.$customer->name);
});

//  Tell the user, once the changes are saved
return redirect()
    ->route('customers.index')
    ->alert('success', trans('alerts.customers.deleted', ['name' => $customer->name]));
```

## Prefer constructor injection over service location
Use constructor or method injection, or explicit `new X(...)`, or the class's own static `make()` factory. Do not use `app()` or `resolve()` as a service locator. The only exception is `app(Class::class, [...])` for a class that needs runtime constructor arguments the container cannot resolve.

## Prefer helpers over facades
Use `route()`, `view()`, `redirect()`, `config()`, `auth()`, `response()`, `now()`, `today()` and the shared helpers (`user()`, `money()`, `prettify()`...) instead of their facades. Keep `Gate::` for authorization and `DB::` / `Storage::` for side-effecting facades.

## Always use route() for URLs
Generate URLs with named routes via `route()`. Never use `url()` or `action()`.

## Collection pipelines and fluent strings
Use `collect()->map()->filter()` chains instead of `array_map` / `array_filter`, and the fluent `str()` helper instead of `Str::` or native string functions for producing strings. Use `foreach` only for side effects. Reserve native `trim()` / `strtolower()` for simple checks and comparisons.

## now() and today() for dates
Use `now()` / `today()` for the current moment. Use `Carbon::parse()`, `create()` or `createFromFormat()` only for externally supplied date input.

## whereBelongsTo() for relation-scoped queries
Scope a query to a related model with `->whereBelongsTo($model)` (optionally `, 'relation'`) instead of `where('x_id', $model->id)`. Fall back to `where()` only when there is no relationship to hang it off. Tenant-owned data follows the tenant rule in models.md instead.

## updateOrCreate / firstOrCreate over find-then-save
When a record may or may not exist, use `updateOrCreate()` / `firstOrCreate()` keyed on a natural unique constraint. Do not fetch and conditionally `save()`.

## One relation per line in ->load()
When eager-loading more than one relation with `->load([...])`, put each relation on its own line. Query-builder `->with([...])` calls stay inline. A single relation stays inline either way.

## Prefer enums over constants and switch
Represent fixed option sets with string-backed enums and `match`, not class constants or `switch`.

## Never hardcode currency
Use `money()` / `currency()` (or a model's `HasMoney` methods) so display follows `config('essentials.currency')`. Never write "KES" or "Ksh" in PHP output, messages or PDFs.

## No DTO layer
Do not add DTO or readonly value-object classes. Use arrays or `fluent()`.

## Keep dead code out
No empty stub methods, no commented-out methods or classes, and no routes pointing at them. Implement the action or delete it and any UI that calls it.

## Stateless helpers vs services
Stateless utility functions with no dependencies (formatting, casting, paths) are global functions guarded by `function_exists()`. Anything that needs service resolution, view rendering or shared state is a class under `app/Services`, exposed through a facade when a static entry point is needed.

## Formatting helpers are forgiving
Helpers that format a value accept null or bad input and return it unchanged. They never throw, and date helpers return a string, not a `Carbon`.

## Period filters use the request macro
Parse a report's period (a day, month, year or a `from to to` range of days, months or years) with `request()->period('month')`, which returns the `from` and `to` dates. Do not parse the period input by hand. In an app with `nyumbani/ui`, the input that sends it is `<x-nyumbani::period-picker>` (see the nyumbani-period-picker skill).

## Copy a Carbon date before changing it
Carbon dates are mutable, so `startOfDay()`, `addDay()` and every other modifier changes the instance it is called on. Call `->copy()` first whenever the original must stay as it was, such as when one date feeds two results (`$from->copy()->endOfMonth()`) or a date is stored on an object. Do not switch to `CarbonImmutable`.
