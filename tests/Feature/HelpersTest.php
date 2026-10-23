<?php

use Illuminate\Support\Facades\Blade;

/**
 * Pin the currency so the expected wording does not depend on the environment
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
beforeEach(function () {
    config(['essentials.currency' => 'KES']);
});

/**
 * Prove number_in_words() spells whole numbers, including zero, hundreds and the large scales
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('spells whole numbers in words', function (mixed $number, string $words) {
    expect(number_in_words($number))->toBe($words);
})->with([
    'zero' => [0, 'Zero'],
    'teen' => [13, 'Thirteen'],
    'tens' => [45, 'Forty Five'],
    'hundred' => [100, 'One Hundred'],
    'thousands' => [1250, 'One Thousand Two Hundred Fifty'],
    'millions' => [3250000, 'Three Million Two Hundred Fifty Thousand'],
    'gaps' => [1000001, 'One Million One'],
    'trillions' => [2000000000000, 'Two Trillion'],
    'numeric string' => ['1500', 'One Thousand Five Hundred'],
    'whole float' => ['12.0', 'Twelve'],
]);

/**
 * Prove number_in_words() returns null, bad, negative, fractional and too-large input unchanged
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('returns input it cannot spell as a whole number unchanged', function (mixed $number) {
    expect(number_in_words($number))->toBe($number);
})->with([
    'null' => [null],
    'text' => ['abc'],
    'negative' => [-5],
    'fraction' => [12.7],
    'above PHP_INT_MAX' => [1e19],
]);

/**
 * Prove amount_in_words() reads amounts the way a receipt does
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('spells amounts in receipt wording', function (mixed $amount, ?string $currency, string $words) {
    expect(amount_in_words($amount, $currency))->toBe($words);
})->with([
    'one million' => [1000000, null, 'One Million Kenya Shillings Only'],
    'with cents' => [3250000.50, null, 'Three Million Two Hundred Fifty Thousand Kenya Shillings and Fifty Cents Only'],
    'dollars and cents' => [1500.50, 'USD', 'One Thousand Five Hundred US Dollars and Fifty Cents Only'],
    'numeric string' => ['1500.50', null, 'One Thousand Five Hundred Kenya Shillings and Fifty Cents Only'],
    'zero' => [0, null, 'Zero Kenya Shillings Only'],
    'hundred' => [100, null, 'One Hundred Kenya Shillings Only'],
    'one shilling' => [1, null, 'One Kenya Shilling Only'],
    'one dollar' => [1, 'USD', 'One US Dollar Only'],
    'one cent' => [0.01, null, 'One Cent Only'],
    'cents only' => [0.5, null, 'Fifty Cents Only'],
    'one and one' => [1.01, null, 'One Kenya Shilling and One Cent Only'],
    'lower-case code' => [1500, 'usd', 'One Thousand Five Hundred US Dollars Only'],
    'unknown code' => [10, 'XYZ', 'Ten XYZ Only'],
    'rounded to cents' => [1.005, null, 'One Kenya Shilling Only'],
]);

/**
 * Prove amount_in_words() takes its default currency from essentials.currency
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('defaults to the configured currency', function () {
    config(['essentials.currency' => 'UGX']);

    expect(amount_in_words(2))->toBe('Two Uganda Shillings Only');
});

/**
 * Prove amount_in_words() returns null, bad, negative and too-large input unchanged
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('returns amounts it cannot spell unchanged', function (mixed $amount) {
    expect(amount_in_words($amount))->toBe($amount);
})->with([
    'null' => [null],
    'text' => ['abc'],
    'negative' => [-5],
    'too large' => [1e17],
]);

/**
 * Prove the @amountinwords directive prints the amount in words, with or without a currency
 *
 * @author Mark Kilonzi <markkilonzi24@gmail.com>
 */
it('prints amounts in words with the Blade directive', function () {
    expect(Blade::render('@amountinwords($amount)', ['amount' => 1500.5]))
        ->toBe('One Thousand Five Hundred Kenya Shillings and Fifty Cents Only')
        ->and(Blade::render("@amountinwords(\$amount, 'USD')", ['amount' => 1]))
        ->toBe('One US Dollar Only');
});
