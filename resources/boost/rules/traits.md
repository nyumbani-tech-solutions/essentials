---
paths:
  - 'app/Traits/**'
---

# Traits

## Prefer the shared traits
Use the traits from `Nyumbani\Essentials\Traits` (`HasUlids`, `HasUploads`, `HasProfileImage`, `HasEnumElements`, `HasNumberIdentifiers`, `HasMoney`...) instead of keeping a local copy. Only app-specific traits live in `app/Traits`.

## Model mixins as HasX traits
Model mixins are `HasX` traits using `bootHasX()` / `initializeHasX()` hooks. `HasUlids` is the default public-key strategy; `HasNumberIdentifiers` is for sequential business-number columns. Controller helpers such as `StoresProfileImages` are traits too.

## Know what each shared trait needs
- `HasUlids`: a unique `ulid` column; the `ulid` is the route key, so read `getRouteKey()`, never `$model->ulid`.
- `HasNumberIdentifiers`: a `number` column; the year comes from `created_at`, and `project_id` or `organization_id` scopes the sequence.
- `HasEnumElements`: `label()` reads the case name, so name cases in `UPPER_SNAKE_CASE` (`PARTIALLY_PAID` gives "Partially Paid", `PartiallyPaid` gives "Partiallypaid").
- `HasProfileImage`: the model's `uploads()` is a `morphMany(Upload::class, 'owner')`, and the Upload table needs `type` (`profile-image`), `path`, `disk` and `size`.
- `HasMoney`: `money()` is absolute; use `relativeMoney()` to keep a sign.
- `HasKenyans`: factory and seeder use only; its phone number is one digit too long on purpose, and `generateMpesaTransactionCode($date)` takes the payment date, so a back-dated payment gets a code that sorts with it. Do not write a local M-Pesa code generator.

## Events only for message dispatch
Reserve event dispatch for decoupling notification delivery (SMS / email) from the workflow that triggers it. Call services and models directly for all other business logic. No model observers or `booted()` closures for business events.
