@extends('docs::layout')

@section('page', 'Helpers')
@section('lead', 'The same formatting in PHP, Blade and JavaScript, so a figure looks identical everywhere.')

@section('content')
    <x-docs::section id="php" title="PHP helpers">
        <x-docs::table :columns="['Helper', 'Returns', 'Example']" :rows="[
            ['user()', 'The signed-in user or null', 'user()->name'],
            ['money($amount, $showCurrency = true)', 'Formatted money', 'money(1500) gives KES 1,500.00'],
            ['numeric($number, $decimals = 2)', 'A number with separators', 'numeric(1200.5) gives 1,200.50'],
            ['percentage($value, $total)', 'The share of a total as a formatted number, or 0.00 when the total is not above 0', 'percentage(25, 200) gives 12.50'],
            ['money_in_words($amount, $currency = null)', 'A money amount in words for receipts, rounded to whole cents', 'money_in_words(1000000) gives One Million Kenya Shillings Only'],
            ['number_in_words($number)', 'A whole number up to PHP_INT_MAX in words; fractions come back unchanged', 'number_in_words(3250000) gives Three Million Two Hundred Fifty Thousand'],
            ['currency()', 'The currency symbol', 'KES'],
            ['prettify($text)', 'Title Case from snake or kebab case', 'prettify(\'order_status\') gives Order Status'],
            ['format_date($date)', 'A date', '12 Mar 2026 (05 Nov 2026 for single-digit days)'],
            ['format_time($date, 12)', 'A time, 12 or 24 hour clock', '02:30 PM (24: 14:30:00)'],
            ['format_date_time($date, 12)', 'A date and time', '12 Mar 2026 02:30 PM'],
            ['format_humans($date)', 'A relative time', '2 hours ago'],
            ['format_minutes($minutes)', 'Hours and minutes', '1h 30m'],
        ]" />
        <x-docs::playground demo="money" title="Try the number helpers" :fields="[
            ['name' => 'amount', 'label' => 'Amount', 'value' => '1500.5', 'placeholder' => 'e.g. 1500.5, or text to see it left alone'],
            ['name' => 'total', 'label' => 'Total, for the percentage', 'value' => '6000', 'placeholder' => 'e.g. 6000'],
        ]" />
        <x-docs::playground demo="dates" title="Try the date helpers" :fields="[
            ['name' => 'date', 'label' => 'Pick a date and time', 'type' => 'datetime', 'value' => '2026-03-12T14:30'],
            ['name' => 'text', 'label' => 'Or type any text (wins over the picker)', 'value' => '', 'placeholder' => 'e.g. tomorrow, or not a date to see it returned unchanged'],
        ]" />
        <x-docs::playground demo="text" title="Try the text helpers" :fields="[
            ['name' => 'text', 'label' => 'Text to prettify', 'value' => 'order_status-paid', 'placeholder' => 'e.g. order_status'],
            ['name' => 'minutes', 'label' => 'Minutes', 'value' => '90', 'placeholder' => 'e.g. 90'],
        ]" />
        <p>The formatting helpers are forgiving. <code>numeric()</code> and <code>money()</code> leave a value that is not a number as it is, and the date helpers return a value they cannot parse as it came, so a bad value shows up on the page instead of crashing it.</p>
        <x-docs::code>
            money(1500);                  // KES 1,500.00
            money(1500, false);           // 1,500.00
            money(null);                  // null
            numeric('n/a');               // n/a
            percentage(25, 200);          // 12.50
            percentage(25, 0);            // 0.00
            money_in_words(1500.50);     // One Thousand Five Hundred Kenya Shillings and Fifty Cents Only
            money_in_words(1, 'usd');    // One US Dollar Only
            money_in_words(0.5);         // Fifty Cents Only
            money_in_words(1.005);       // One Kenya Shilling Only (rounded to whole cents)
            money_in_words(null);        // null
            number_in_words(12.7);        // 12.7
            format_date('2026-03-12');    // 12 Mar 2026
            format_date('not a date');    // not a date
            format_minutes(90);           // 1h 30m
            format_minutes(45);           // 45m
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="blade" title="Blade directives">
        <p>Each helper has a directive, so views never need <code>@@php</code>.</p>
        <x-docs::code lang="blade">
            @verbatim
            @money($invoice->total)
            @moneyinwords($receipt->amount)
            @numberinwords($count)
            @numeric($count, 0)
            @percentage($paid, $total)
            @date($invoice->due_at)
            @time($visit->starts_at)
            @datetime($order->created_at)
            @dateforhumans($order->created_at)
            @prettify($status)
            @currency
            @bag('row-1')
            @endverbatim
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="js" title="JavaScript helpers">
        <p>The same names exist on <code>window</code> once <code>helpers.js</code> is loaded, and read the currency from the <code>data-currency</code> attribute on <code>&lt;html&gt;</code>, which the UI package's layout sets from <code>essentials.currency</code>. An app without that layout sets the attribute itself. <code>money_in_words()</code> and <code>number_in_words()</code> are PHP-only, as receipts are rendered on the server.</p>
        <x-docs::code lang="js">
            money(1500);               // KES 1,500.00
            numeric(1200.5, 2);        // 1,200.50
            numeric(1200.5);           // 1,201 (no decimals unless you ask)
            parseNumber('1,200.50');   // 1200.5
            pluralize('invoice', 3);   // invoices
            prettify('order_status');  // Order Status
            formatDate(order.created_at);      // 12 Mar 2026
            formatDateTime(order.created_at);  // Thu, 12 Mar 2026, 02:30 pm
        </x-docs::code>
        <x-docs::callout type="warning" title="The JavaScript helpers differ in three ways">
            <ul class="list-disc pl-5">
                <li><code>numeric()</code> defaults to <strong>0</strong> decimals in JavaScript and <strong>2</strong> in PHP, so pass the decimals when you need both to match.</li>
                <li><code>formatDateTime()</code> adds the weekday and a lower-case <code>pm</code>; <code>format_date_time()</code> in PHP does not.</li>
                <li>A date JavaScript cannot read gives <code>Invalid date</code> (or <code>Not provided</code> when empty), where PHP returns the input unchanged.</li>
            </ul>
        </x-docs::callout>
    </x-docs::section>
@endsection
