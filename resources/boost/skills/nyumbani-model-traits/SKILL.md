---
name: nyumbani-model-traits
description: "Invoke when writing or changing an Eloquent model, an enum, an Upload model or a factory in a Nyumbani app: ulid route keys (HasUlids), HasMorphRouteKey, year-scoped numbers (HasNumberIdentifiers), money on a model (HasMoney), enum labels (HasEnumElements), uploads and profile images (HasUploads, HasProfileImage), or Kenyan fake names and numbers (HasKenyans). Skip for roles and permissions traits (see nyumbani-permissions)."
metadata:
  author: nyumbani
---

# Nyumbani Model Traits

Use these traits from `Nyumbani\Essentials\Traits` instead of writing your own. Never use Laravel's own `HasUlids`: the Nyumbani one keeps the auto-increment `id` internal.

| Trait | Gives |
| --- | --- |
| `HasUlids` | A lower-case ULID in the `ulid` column on create, used as the route key. |
| `HasMorphRouteKey` | `getMorphRouteKey()`, formatted `type:key`, for polymorphic route binding. |
| `HasNumberIdentifiers` | A year-scoped sequential `number` such as `WOR-2026-0001`. |
| `HasMoney` | `currencySymbol()`, `money()` and `relativeMoney()`. |
| `HasEnumElements` | `label()`, `lowerLabel()` and `random()` for backed enums. |
| `HasUploads` | File behaviour for the app's `Upload` model. |
| `HasProfileImage` | A replaceable profile image with an avatar fallback. |
| `HasKenyans` | Kenyan names and safe fake phone numbers for factories. |

## Ulids
Migration: `$table->id(); $table->ulid()->unique();`. The trait fills `ulid` on create (unless set) and makes it the route key, so route model binding uses it. Read it with `$customer->getRouteKey()`, never `$customer->ulid`, and build URLs with the model: `route('customers.show', ['customer' => $customer])`.
```php
use Illuminate\Database\Eloquent\Model;
use Nyumbani\Essentials\Traits\HasUlids;

class Customer extends Model
{
    use HasUlids;
}
```

## Number identifiers
Needs a `number` column. The number is prefix, year, four digits; the sequence restarts each year. The prefix is the first three letters of the class name; override `$numberPrefix`. The year comes from `created_at` (override `$numberDateColumn`). A model with `project_id` or `organization_id` counts a sequence for each. A number you set yourself is kept.
```php
use Illuminate\Database\Eloquent\Model;
use Nyumbani\Essentials\Traits\HasNumberIdentifiers;

class WorkOrder extends Model
{
    use HasNumberIdentifiers;

    protected ?string $numberPrefix = 'WOR';
}
```

## Money
`$invoice->money($invoice->total)` gives `KES 1,500.00`; `currency: false` drops the symbol; the amount is absolute by default and `relativeMoney(-250)` keeps the sign (`KES -250.00`). The symbol is `config('essentials.currency')`: never write "KES" in code (see nyumbani-helpers for the global `money()`).

## Enum elements
```php
use Nyumbani\Essentials\Traits\HasEnumElements;

enum InvoiceStatus: string
{
    use HasEnumElements;

    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially-paid';
}

InvoiceStatus::PARTIALLY_PAID->label();       // Partially Paid
InvoiceStatus::PARTIALLY_PAID->lowerLabel();  // partially_paid
InvoiceStatus::random();                      // for factories
```
`label()` reads the case name, not the value: name cases in `UPPER_SNAKE_CASE`.

## Uploads and profile images
The app owns its `Upload` model and `UploadType` enum. The upload table needs `path`, `disk` (cast to `Nyumbani\Essentials\Enums\UploadDisk`, `public` or `local`, default `public`; only public uploads get a `url`), `size` (filled on save), `type` (`profile-image` for a profile image) and the morph columns `owner_type`, `owner_id` (the relation is named `owner`). `HasUploads` gives `url`, `formatted_size`, `extension` and `isImage()`, and deletes the file when the row is deleted.
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Nyumbani\Essentials\Traits\HasProfileImage;
use Nyumbani\Essentials\Traits\HasUlids;
use Nyumbani\Essentials\Traits\HasUploads;

class Upload extends Model
{
    use HasUlids, HasUploads;
}

class User extends Authenticatable
{
    use HasProfileImage;

    public function uploads(): MorphMany
    {
        return $this->morphMany(Upload::class, 'owner');
    }
}

$user->hasProfileImage();
$user->avatar;   // the newest profile-image url, or a generated avatar from ui-avatars.com
```
In a form, use the file input from `nyumbani/ui` (see nyumbani-ui-theme).

## Factories
```php
use Illuminate\Database\Eloquent\Factories\Factory;
use Nyumbani\Essentials\Traits\HasKenyans;

class CustomerFactory extends Factory
{
    use HasKenyans;

    public function definition(): array
    {
        return [
            'name' => $this->generateKenyanName(),
            'phone_number' => $this->generateKenyanPhoneNumber(),
        ];
    }
}
```
The fake number is one digit too long on purpose, so a seeded or test record can never SMS a real person. It therefore fails `KenyanNumberValid` (see nyumbani-validation).
