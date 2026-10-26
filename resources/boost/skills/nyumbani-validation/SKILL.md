---
name: nyumbani-validation
description: "Invoke when validating input in a Nyumbani app: Form Requests, the phone rules KenyanNumberValid and PhoneNumberValid, the CleanName cast for names, friendly attribute names, or password rules. Skip for several forms on one page (see nyumbani-error-bags) and for sign-in (see nyumbani-auth)."
metadata:
  author: nyumbani
---

# Nyumbani Validation

Validate in a Form Request, never inline in a controller. Keep each field's rules in a named variable and give friendly names with `attributes()`.
```php
use Illuminate\Foundation\Http\FormRequest;
use Nyumbani\Essentials\Rules\KenyanNumberValid;

class StoreCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        $name = ['required', 'string', 'max:255'];
        $phone = ['required', new KenyanNumberValid];

        return ['name' => $name, 'phone' => $phone];
    }

    public function attributes(): array
    {
        return ['phone' => 'phone number'];
    }
}
```

## Rules
Both live in `Nyumbani\Essentials\Rules`.

| Rule | Accepts |
| --- | --- |
| `KenyanNumberValid` | A Kenyan mobile: optional `0`, `254` or `+254`, then `7xx` or `10x`/`11x`, then six more digits. `0712345678`, `254712345678`, `+254712345678`, `712345678` and `0110123456` pass; `071234567` (a digit short) and `0212345678` (a landline) fail. |
| `PhoneNumberValid` | An international number of 7 to 15 digits with an optional leading `+`. |

Use `KenyanNumberValid` for anything that gets an SMS or an M-Pesa prompt. Normalise a number before sending with the helper the sending package provides (`daraja_phone()` for Daraja; the message builder for Messaging).

## CleanName cast
Collapses repeated spaces and title-cases a name on the way in; reading is unchanged. It only cleans non-empty strings.
```php
use Nyumbani\Essentials\Casts\CleanName;

protected function casts(): array
{
    return ['name' => CleanName::class];
}

//  "  jane    WANJIRU " is stored as "Jane Wanjiru"
```
Do not clean names in the controller or the request: the cast does it for every write.

## Passwords
Passwords need at least 8 characters, through `Password::defaults()`, which Essentials sets. Use `Password::defaults()` in your own rules rather than `Password::min()`.

## Messages
Validation messages and flash texts are lang keys, never hard-coded sentences (see the lang rule). Several forms on one page each need their own error bag: see nyumbani-error-bags.
