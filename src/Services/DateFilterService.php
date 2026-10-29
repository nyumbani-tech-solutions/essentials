<?php

namespace Nyumbani\Essentials\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Fluent;

/**
 * Parse a period filter (a day, month, year or a range of them) into the dates a report is scoped to. Input
 * that is not a valid period falls back to the default period instead of failing.
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class DateFilterService
{
    /**
     * Parse a period string such as "2026", "2026-03", "2026-03-14" or "2026-01-01 to 2026-03-31" into a
     * fluent object with `date`, `year`, `month`, `from`, `to` and `formatted`, where `year` and `month`
     * are null when the period spans more than one of them
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function parse(?string $value, string $default = 'month'): Fluent
    {
        //  Trim the input
        $value = trim((string) $value);

        //  Try each format in turn, falling back to the default period
        return $this->fromRange($value)
            ?? $this->fromDate($value)
            ?? $this->fromMonth($value)
            ?? $this->fromYear($value)
            ?? $this->defaultPeriod($default);
    }

    /**
     * Get the current day, month or year, which is the month unless the default is "day" or "year"
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function defaultPeriod(string $default): Fluent
    {
        //  Get the current moment
        $now = now();

        //  Build the period for the default
        return match ($default) {
            'day' => $this->make($now->copy()->startOfDay(), $now->copy()->endOfDay(), $now->format('Y-m-d')),
            'year' => $this->make($now->copy()->startOfYear(), $now->copy()->endOfYear(), $now->format('Y'), hasMonth: false),
            default => $this->make($now->copy()->startOfMonth(), $now->copy()->endOfMonth(), $now->format('Y-m')),
        };
    }

    /**
     * Parse a range of days, months or years (e.g. "2026-01-01 to 2026-03-31", "2026-01 to 2026-03" or "2024 to 2026"),
     * whose two ends are the same kind and which must not end before it starts
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function fromRange(string $value): ?Fluent
    {
        //  Split the range into its two ends
        $parts = explode(' to ', $value, 2);

        //  Read each end as a day, month or year
        $start = $this->end($parts[0]);
        $finish = $this->end($parts[1] ?? '');

        //  Reject a range that is incomplete, mixes kinds or ends before it starts
        if (! $start || ! $finish || $start['kind'] !== $finish['kind'] || $finish['to']->lt($start['from'])) {
            return null;
        }

        //  Build the period
        return $this->make($start['from'], $finish['to'], $value, hasYear: false, hasMonth: false);
    }

    /**
     * Read one end of a range as a day, month or year, giving its kind and the first and last moment it covers, or null for anything else
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function end(string $value): ?array
    {
        //  Trim the end
        $value = trim($value);

        //  A day covers itself
        if ($day = $this->date($value)) {
            return ['kind' => 'day', 'from' => $day->copy()->startOfDay(), 'to' => $day->copy()->endOfDay()];
        }

        //  A month covers its first to last day
        if ($month = $this->month($value)) {
            return ['kind' => 'month', 'from' => $month->copy()->startOfMonth(), 'to' => $month->copy()->endOfMonth()];
        }

        //  A year covers January to December
        if ($year = $this->year($value)) {
            return ['kind' => 'year', 'from' => $year->copy()->startOfYear(), 'to' => $year->copy()->endOfYear()];
        }

        return null;
    }

    /**
     * Parse a full date (e.g. "2026-01-14")
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function fromDate(string $value): ?Fluent
    {
        //  Parse the date
        $date = $this->date($value);

        //  Reject anything that is not a real date
        return $date
            ? $this->make($date->startOfDay(), $date->endOfDay(), $value)
            : null;
    }

    /**
     * Parse a month (e.g. "2026-03"), which must be a real month
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function fromMonth(string $value): ?Fluent
    {
        //  Read the month
        $date = $this->month($value);

        //  Build the period
        return $date
            ? $this->make($date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $value)
            : null;
    }

    /**
     * Parse a year (e.g. "2026")
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function fromYear(string $value): ?Fluent
    {
        //  Read the year
        $date = $this->year($value);

        //  Build the period
        return $date
            ? $this->make($date->copy()->startOfYear(), $date->copy()->endOfYear(), $value, hasMonth: false)
            : null;
    }

    /**
     * Parse a real month such as "2026-03" to its first day, or null for anything else (including "2026-13")
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function month(string $value): ?Carbon
    {
        //  Match the year and month, with the month between 1 and 12
        if (! preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches) || ! checkdate((int) $matches[2], 1, (int) $matches[1])) {
            return null;
        }

        //  Build the first day of the month
        return Carbon::create((int) $matches[1], (int) $matches[2], 1);
    }

    /**
     * Parse a four digit year such as "2026" to its first day, or null for anything else
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function year(string $value): ?Carbon
    {
        //  Match a four digit year, and build its first day
        return preg_match('/^\d{4}$/', $value)
            ? Carbon::create((int) $value, 1, 1)
            : null;
    }

    /**
     * Parse a real calendar date such as "2026-01-14", or null for anything else (including "2026-02-31")
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function date(string $value): ?Carbon
    {
        //  Match the year, month and day, and check they form a real date
        if (! preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim($value), $matches) || ! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        //  Build the date
        return Carbon::create((int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }

    /**
     * Build a period from its first and last moments, giving it its own copy of the date and leaving the year and month null when it spans several
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function make(Carbon $from, Carbon $to, string $formatted, bool $hasYear = true, bool $hasMonth = true): Fluent
    {
        return fluent([
            'date' => $from->copy(),
            'year' => $hasYear ? $from->year : null,
            'month' => $hasMonth ? $from->month : null,
            'from' => $from,
            'to' => $to,
            'formatted' => $formatted,
        ]);
    }
}
