<?php

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

//  Date and time helper functions

if (! function_exists('format_date')) {
    /**
     * Format dates to a global date format, leaving unparsable values as they are
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function format_date(string|Carbon|null $date): ?string
    {
        //  Keep an empty value empty
        if (is_null($date)) {
            return null;
        }

        //  Parse and format the date
        try {
            $date = Carbon::parse($date)->format('d M Y');
        } catch (InvalidFormatException $exception) {
            //  Keep the original value
        }

        //  Return the date
        return (string) $date;
    }
}

if (! function_exists('format_time')) {
    /**
     * Format dates to a global time format, in 12 or 24 hour clock
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function format_time(string|Carbon|null $date, int $format = 12): ?string
    {
        //  Keep an empty value empty
        if (is_null($date)) {
            return null;
        }

        //  Parse and format the time
        try {
            //  Parse the date
            $date = Carbon::parse($date);

            //  Determine the correct format
            $date = $format == 24
                ? $date->format('H:i:s')
                : $date->format('h:i A');
        } catch (InvalidFormatException $exception) {
            //  Keep the original value
        }

        //  Return the time
        return (string) $date;
    }
}

if (! function_exists('format_date_time')) {
    /**
     * Format dates to a global date time format, in 12 or 24 hour clock
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function format_date_time(string|Carbon|null $date, int $format = 12): ?string
    {
        //  Keep an empty value empty
        if (is_null($date)) {
            return null;
        }

        //  Parse and format the date time
        try {
            //  Parse the date
            $date = Carbon::parse($date);

            //  Determine the correct format
            $date = $format == 24
                ? $date->format('d M Y H:i:s')
                : $date->format('d M Y h:i A');
        } catch (InvalidFormatException $exception) {
            //  Keep the original value
        }

        //  Return the date
        return (string) $date;
    }
}

if (! function_exists('format_minutes')) {
    /**
     * Format a minutes value into a human-readable hours/minutes label, e.g. "1h 30m"
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function format_minutes(int $minutes): string
    {
        //  Get the whole hours and remaining minutes
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        //  Return a minutes-only label when there are no whole hours
        if ($hours === 0) {
            return "{$remaining}m";
        }

        //  Build the formatted string showing hours and minutes separately
        return $remaining > 0 ? "{$hours}h {$remaining}m" : "{$hours}h";
    }
}

if (! function_exists('format_humans')) {
    /**
     * Format dates as a human readable difference, e.g. "2 hours ago"
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    function format_humans(string|Carbon|null $date): ?string
    {
        //  Keep an empty value empty
        if (is_null($date)) {
            return null;
        }

        //  Parse and format the date
        try {
            $date = Carbon::parse($date)->diffForHumans();
        } catch (InvalidFormatException $exception) {
            //  Keep the original value
        }

        //  Return the date
        return (string) $date;
    }
}
