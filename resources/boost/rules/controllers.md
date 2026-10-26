---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Audience sub-namespaces
Controllers live in plural audience folders (`Admins/`, `SystemAdmins/`, `Customers/`, `Vendors/`), matching Requests, Services and view folders. Shared top-level controllers are only for routes every authenticated user can reach.

## Plain multi-method controllers
Controllers are plain classes grouped by resource, with custom action methods (e.g. `activate`, `archive`) and `__invoke` for single-action pages like dashboards. Do not use `Route::resource`.

## Authorize with Gate::authorize()
Call `Gate::authorize('ability', $model)` at the top of each action. Never use `$this->authorize()`, `->can()` or route-level `can:` middleware. Blade uses `@can` only for conditional UI.

## Validate via Form Requests
All input validation uses Form Request classes type-hinted in the method signature. Never use inline `$request->validate()` or `Validator::make()`. Read data with `$request->safe()->field` or `->validated()`, not `input()`. Typed getters are only for boolean, date and enum input (see the typed accessors rule).

## Thin controllers delegate to model methods
Keep controllers thin: authorize with `Gate::authorize`, extract the validated data, then call an expressive method on the relevant model (e.g. `$customer->createBid()`, `$invoice->isFullyPaid()`) to do the actual business logic. Do not write it inline in the action. There is no repository or query-object layer; query Eloquent directly. Use `app/Services` only for read-side aggregation (e.g. dashboard data), injected through method injection.

## Implicit route-model binding
Type-hint models in action signatures. Never `findOrFail()` manually or use `Route::bind()` for it. Never bind both a parent and its child model in one action; bind the child and reach the parent through its relation (eager-loading it for authorization), and drop the unused parent URI segment. Two independent sibling models scoped together are fine.

## Create through the tenant relation
Create tenant-owned records through the tenant's relation, e.g. `$tenant->customers()->create($validated)`, never `Customer::create($validated + ['organization_id' => ...])`. The tenant id is never read from the request or a hidden form field.

## Log every mutation
Every create, update, activate or deactivate records an activity with a kebab-case `subject-verbed` event name. The call has four links, so it is chained one per line, as the chaining rule in `app.md` says.

Write the change and its log inside one `DB::transaction()`, as the transaction rule in `app.md` says.

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
```

## Flash feedback through __()
Redirect with `->alert('success'|'error'|'warning', __('...'))`, where the key is the English sentence or a nested alerts key such as `alerts.<domain>.<action>`. Messages are full sentences with `:placeholder` interpolation. Never pass an untranslated string.

## Index listings are newest-first
Order plain listings with `->latest('id')` (qualified with the table for joined queries), not `latest()`. Skip this for manually ranked lists, grouped dashboards and inherently chronological views.

## Views receive fluent objects
Convert service-layer arrays with `fluent($array)` before passing them to views, so views read `$data->property`. Map nested arrays with `fluent($data)->map(fn ($item) => fluent($item))`.

## Name the parameters in route()
Pass route parameters as a keyed array, never a bare model: `route('customers.show', ['customer' => $customer])`, not `route('customers.show', $customer)`. This applies in views, controllers, tests and anywhere else `route()` is called.

## Typed accessors only for boolean, date and enum input
Read boolean, date and enum request input with `->boolean()`, `->date()` and `->enum()`. Use plain property or array access for strings and numbers, not `->string()` or `->integer()`.
