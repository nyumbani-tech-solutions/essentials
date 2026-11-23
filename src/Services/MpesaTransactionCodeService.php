<?php

namespace Nyumbani\Essentials\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Generate M-Pesa transaction codes the way Safaricom issues them: a year letter, a month letter, a day character
 * and a seven character base-36 counter shared by every M-Pesa transaction, so codes sort in time order. The counter
 * starts from one real code and follows an estimated traffic curve, so it is a good fake and not an exact one.
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class MpesaTransactionCodeService
{
    /**
     * The timezone the counter was observed in, which has no daylight saving
     */
    protected const TIMEZONE = 'Africa/Nairobi';

    /**
     * Seconds the timezone is ahead of UTC
     */
    protected const UTC_OFFSET = 10800;

    /**
     * The first year letter: Q is 2022
     */
    protected const FIRST_YEAR = 2022;

    /**
     * The largest counter a seven character base-36 tail can hold
     */
    protected const MAX_COUNTER = 78364164095;

    /**
     * The Nairobi time of the real code the counter starts from
     */
    protected const ANCHOR_TIME = '2026-11-23 09:00';

    /**
     * The counter tail of that real code
     */
    protected const ANCHOR_TAIL = '5A3ZVN3';

    /**
     * Transactions a second for each hour of the day (0 to 23), averaging 2.5 a second as the real codes do
     *
     * @var array<int, float>
     */
    protected const HOURLY_RATES = [
        0.7, 0.5, 0.4, 0.4, 0.5, 0.7, 1.1, 1.8, 2.6, 3.6, 3.8, 3.6,
        3.5, 3.5, 3.6, 3.7, 4.1, 4.4, 4.5, 4.4, 4.0, 2.0, 1.4, 1.0,
    ];

    /**
     * Create the service
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Generate the transaction code for a moment, today by default
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function generate(?CarbonInterface $date = null): string
    {
        //  Read the moment in the timezone Safaricom uses
        $moment = ($date ?? now())->copy()->setTimezone(self::TIMEZONE);

        //  Build the date prefix and the counter tail
        return $this->prefix($moment).$this->tail($moment);
    }

    /**
     * Get the year, month and day characters
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function prefix(CarbonInterface $moment): string
    {
        //  Year letter: Q for 2022, wrapping after Z
        $yearLetter = chr(ord('A') + (((($moment->year - self::FIRST_YEAR) % 26) + 26 + 16) % 26));

        //  Month letter: A for January to L for December
        $monthLetter = chr(ord('A') + $moment->month - 1);

        //  Day character: 1 to 9, then A for the 10th to V for the 31st
        $dayCharacter = base_convert((string) $moment->day, 10, 32);

        return strtoupper("{$yearLetter}{$monthLetter}{$dayCharacter}");
    }

    /**
     * Get the seven character counter for a moment, with a few steps of jitter so codes in the same second differ
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function tail(CarbonInterface $moment): string
    {
        //  Estimate the counter and add the jitter
        $counter = $this->counterAt($moment->getTimestamp()) + fake()->numberBetween(0, 3);

        //  Keep it within seven base-36 characters
        $counter = max(0, min(self::MAX_COUNTER, $counter));

        //  Pad with leading zeros
        return strtoupper(str_pad(base_convert((string) $counter, 10, 36), 7, '0', STR_PAD_LEFT));
    }

    /**
     * Estimate the counter at a timestamp by following the traffic curve from the anchor, forwards or backwards
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function counterAt(int $timestamp): int
    {
        //  Read the anchor
        $anchorTimestamp = Carbon::parse(self::ANCHOR_TIME, self::TIMEZONE)->getTimestamp();

        //  Add the transactions made since the anchor
        return (int) floor(
            base_convert(self::ANCHOR_TAIL, 36, 10) + $this->traffic($timestamp) - $this->traffic($anchorTimestamp)
        );
    }

    /**
     * Get the transactions made from the start of time until a timestamp, according to the hourly rates
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function traffic(int $timestamp): float
    {
        //  Work in Nairobi time and split into whole days and the part of the day
        $local = $timestamp + self::UTC_OFFSET;
        $days = intdiv($local, 86400);
        $secondsIntoDay = $local - $days * 86400;
        $wholeHours = intdiv($secondsIntoDay, 3600);

        //  Count the whole days, the whole hours and the part of the current hour
        $total = $days * array_sum(self::HOURLY_RATES) * 3600;
        $total += array_sum(array_slice(self::HOURLY_RATES, 0, $wholeHours)) * 3600;

        return $total + ($secondsIntoDay - $wholeHours * 3600) * self::HOURLY_RATES[$wholeHours];
    }
}
