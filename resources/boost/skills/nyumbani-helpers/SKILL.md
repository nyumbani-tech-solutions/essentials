---
name: nyumbani-helpers
description: "Invoke when formatting money, numbers, percentages, dates, times, durations or text in a Nyumbani app, using the Blade directives or JavaScript equivalents, or scoping a report to a month, year, day or date range with request()->period(). Skip for validation rules and model traits."
metadata:
  author: nyumbani
---

# Nyumbani Helpers

Use these instead of `number_format()`, `date()`, `Carbon::format()` or a literal currency. Never write "KES" or "Ksh" in PHP, Blade or JavaScript output.

## PHP, Blade and JavaScript
| PHP | Blade | Gives |
| --- | --- | --- |
| `money($n)` / `money($n, false)` | `@money($n)` | `KES 1,500.00` / `1,500.00` |
| `numeric($n, $decimals = 2)` | `@numeric($n, 0)` | `1,200.50` |
| `percentage($value, $total)` | `@percentage($paid, $total)` | `12.50`, or `0.00` when the total is not above 0 |
| `money_in_words($n)` / `money_in_words($n, 'USD')` | `@moneyinwords($n)` | `One Million Kenya Shillings Only`, `One Kenya Shilling and Fifty Cents Only`, `Fifty Cents Only`; rounded to whole cents |
| `number_in_words($n)` | `@numberinwords($n)` | `Three Million Two Hundred Fifty Thousand`; fractions and values from `PHP_INT_MAX` come back unchanged |
| `currency()` | `@currency` | the symbol from `essentials.currency` |
| `prettify($text)` | `@prettify($status)` | `Order Status` |
| `format_date($d)` | `@date($d)` | `12 Mar 2026` |
| `format_time($d, 12)` | `@time($d)` | `02:30 PM` (24: `14:30:00`) |
| `format_date_time($d, 12)` | `@datetime($d)` | `12 Mar 2026 02:30 PM` |
| `format_humans($d)` | `@dateforhumans($d)` | `2 hours ago` |
| `format_minutes($m)` | none | `1h 30m` |

The helpers are forgiving: a value that is not a number or a date is returned unchanged, never an exception, and a null date gives null (an empty cell), so a nullable column can be printed directly.

The same names exist on `window` once the essentials bundle is loaded (`money`, `numeric`, `parseNumber`, `pluralize`, `prettify`, `formatDate`, `formatDateTime`), with three differences: `numeric()` defaults to 0 decimals in JavaScript (2 in PHP), `formatDateTime()` adds the weekday and a lower-case `pm`, and an unreadable date gives `Invalid date` instead of the input. `money_in_words()` and `number_in_words()` are PHP-only.

## The period filter
`$request->period()` turns the `period` input into the dates a report queries. Read it once.
```php
use App\Models\Payment;

$period = $request->period();            // no input gives the current month
$period = $request->period('year');      // no input gives the current year; 'day' gives today

Payment::query()->whereBetween('paid_at', [$period->from, $period->to])->get();
```
It reads `2026`, `2026-03`, `2026-03-14` and `2026-01-01 to 2026-03-31`. The result has `from` and `to` (Carbon, covering the whole first and last day), `date`, `year` and `month` (`null` when the period spans more), and `formatted` (the input as a clean string, to put back in the filter field). Anything it cannot read, such as `2026-13`, `2026-02-31`, a range that ends before it starts or free text, falls back to the default period without an error, so show `$period->formatted` to tell the user which period they got. `from` and `to` are mutable Carbon dates: call `copy()` before changing them.
