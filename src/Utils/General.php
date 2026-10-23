<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

//  General helper functions

if (! function_exists('essentials_path')) {
    /**
     * Get the path to a file or folder inside the package.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function essentials_path(string $path = ''): string
    {
        return dirname(__DIR__, 2).($path ? DIRECTORY_SEPARATOR.ltrim($path, '/\\') : '');
    }
}

if (! function_exists('user')) {
    /**
     * Get the authenticated user
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function user(): ?Authenticatable
    {
        return Auth::user();
    }
}

if (! function_exists('prettify')) {
    /**
     * Prettify text, turning snake and kebab case into Title Case
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function prettify(?string $text): ?string
    {
        return $text
            ? ucwords(str_replace('-', ' ', str_replace('_', ' ', strtolower($text))))
            : $text;
    }
}

if (! function_exists('numeric')) {
    /**
     * Format numbers, leaving non-numeric values as they are
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function numeric(mixed $number, int $decimals = 2): mixed
    {
        return ! is_null($number) && is_numeric($number)
            ? number_format($number, $decimals)
            : $number;
    }
}

if (! function_exists('percentage')) {
    /**
     * Get the percentage a value makes of a total, or 0 when the total is not above 0
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function percentage(mixed $value, float $total): mixed
    {
        //  Ensure that the total will always be above 0
        if ($total > 0) {
            $percentage = $value / $total * 100;
        }

        //  Else return 0
        return numeric($percentage ?? 0);
    }
}

if (! function_exists('currency')) {
    /**
     * Get the currency symbol, taken from essentials.currency
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function currency(): string
    {
        return (string) config('essentials.currency', 'KES');
    }
}

if (! function_exists('money')) {
    /**
     * Format money for accounting purposes, optionally prefixed with the currency symbol
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function money(?float $amount = 0, bool $showCurrency = true): mixed
    {
        //  Return null values as they are
        if ($amount === null) {
            return $amount;
        }

        //  Build the formatted amount
        $formattedAmount = numeric($amount);

        //  Prepend the currency symbol if required
        return $showCurrency
            ? currency().' '.$formattedAmount
            : $formattedAmount;
    }
}

if (! function_exists('number_in_words')) {
    /**
     * Spell out a whole number in English, without needing intl
     *
     * @author Mark Kilonzi <markkilonzi24@gmail.com>
     */
    function number_in_words(mixed $number): mixed
    {
        //  Return null, non-numeric and negative values as they are
        if (! is_numeric($number) || $number < 0) {
            return $number;
        }

        $number = (int) $number;
        $ones = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        //  Below twenty every number has its own word
        if ($number < 20) {
            return $ones[$number];
        }

        //  Below a hundred it is a tens word plus any remaining unit
        if ($number < 100) {
            return $tens[intdiv($number, 10)].($number % 10 ? ' '.$ones[$number % 10] : '');
        }

        //  Below a thousand it is a count of hundreds plus whatever is left
        if ($number < 1000) {
            return $ones[intdiv($number, 100)].' Hundred'.($number % 100 ? ' '.number_in_words($number % 100) : '');
        }

        //  Larger numbers split at the biggest scale that fits, largest first
        foreach ([1_000_000_000_000 => 'Trillion', 1_000_000_000 => 'Billion', 1_000_000 => 'Million', 1_000 => 'Thousand'] as $scale => $word) {
            //  Skip scales the number does not reach
            if ($number >= $scale) {
                //  Spell the count of this scale, then the remainder below it
                return number_in_words(intdiv($number, $scale)).' '.$word
                    .($number % $scale ? ' '.number_in_words($number % $scale) : '');
            }
        }

        return (string) $number;
    }
}

if (! function_exists('amount_in_words')) {
    /**
     * Spell out a money amount for receipts, e.g. "One Million Kenya Shillings Only"
     *
     * @author Mark Kilonzi <markkilonzi24@gmail.com>
     */
    function amount_in_words(mixed $amount, ?string $currency = null): mixed
    {
        //  Return null, non-numeric and negative values as they are
        if (! is_numeric($amount) || $amount < 0) {
            return $amount;
        }

        //  Currency codes read as their spoken names; unknown codes stay as the code
        $currency = $currency ?: currency();
        $name = ['KES' => 'Kenya Shillings', 'UGX' => 'Uganda Shillings', 'TZS' => 'Tanzania Shillings', 'USD' => 'US Dollars'][$currency] ?? $currency;

        //  Work in cents so float rounding cannot drop or add a cent
        $cents = (int) round((float) $amount * 100);

        //  Spell the whole units, then any cents after them
        $words = number_in_words(intdiv($cents, 100)).' '.$name;

        //  Only mention cents when there are some
        if ($cents % 100) {
            $words .= ' and '.number_in_words($cents % 100).' Cents';
        }

        return $words.' Only';
    }
}
