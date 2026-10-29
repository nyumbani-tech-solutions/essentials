@extends('docs::layout')

@section('page', 'Period filter')
@section('lead', 'Turn a month, a year, a day or a range typed by a user into the dates a report queries.')

@section('content')
    <x-docs::section id="period" title="The request macro">
        <p>Reports are scoped by a <code>period</code> input. <code>$request->period()</code> turns it into the dates to query.</p>
        <x-docs::code>
            use App\Models\Invoice;

            $period = $request->period();          // defaults to the current month
            $period = $request->period('year');    // or 'day'

            Invoice::whereBetween('created_at', [$period->from, $period->to])->get();
        </x-docs::code>
        <x-docs::table :columns="['Input', 'Meaning']" :rows="[
            ['2026', 'The whole year.'],
            ['2026-03', 'The month.'],
            ['2026-03-14', 'The day.'],
            ['2026-01-01 to 2026-03-31', 'A range of days, which may not end before it starts.'],
            ['2026-01 to 2026-03', 'A range of months: the first day of January to the last day of March.'],
            ['2024 to 2026', 'A range of years: 1 January 2024 to 31 December 2026.'],
        ]" />
        <p>Both ends of a range must be the same kind, so <code>2026-01 to 2026-03-31</code> is not a range and falls back to the default.</p>
        <p>The result has <code>date</code>, <code>year</code>, <code>month</code>, <code>from</code>, <code>to</code> and <code>formatted</code>. Anything it cannot read, including a date like 2026-02-31, falls back to the default period.</p>

        <x-docs::playground demo="period" title="Try a period" :fields="[
            ['name' => 'period', 'label' => 'The period input', 'value' => '2026-03', 'placeholder' => 'e.g. 2026, 2026-03, 2026-03-14, 2026-01 to 2026-03 or 2026-01-01 to 2026-03-31'],
            ['name' => 'default', 'label' => 'Default when the input is empty or invalid', 'type' => 'select', 'value' => 'month', 'options' => ['month' => 'month (the default)', 'day' => 'day', 'year' => 'year']],
        ]" />

        <x-docs::callout type="info" title="The input that sends it">
            In an app with <code>nyumbani/ui</code>, the filter input is <code>&lt;x-nyumbani::period-picker&gt;</code>. It writes exactly the strings above, for a day, month, year or a range of them, so the controller needs nothing extra. See the Period picker page of the UI docs.
        </x-docs::callout>

        <h3>What you get back</h3>
        <p>The period is a <code>Fluent</code> object, so read it with property access. For <code>?period=2026-03</code> (run on any day):</p>
        <x-docs::table :columns="['Property', 'Value', 'Notes']" :rows="[
            ['from', '2026-03-01 00:00:00', 'A Carbon date: the first moment of the period.'],
            ['to', '2026-03-31 23:59:59', 'A Carbon date: the last moment, so whereBetween includes the whole last day.'],
            ['date', '2026-03-01 00:00:00', 'A copy of from. Handy for a month picker or a heading.'],
            ['year', '2026', 'null for a range, since it may span several years.'],
            ['month', '3', 'null for a whole year or a range.'],
            ['formatted', '2026-03', 'The input as a clean string, to put back in the filter field.'],
        ]" />

        <h3>A complete report</h3>
        <p>A report page with a period filter: the form sends <code>period</code>, the controller reads it once, and the view shows what was chosen.</p>
        <x-docs::code lang="blade" title="resources/views/admins/pages/reports/payments.blade.php">
            @verbatim
            <form method="GET" action="{{ route('reports.payments') }}" class="flex gap-3">
                <input type="text" name="period" value="{{ $period->formatted }}" placeholder="e.g. 2026-03, 2026 or 2026-01-01 to 2026-03-31">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <h2>Payments for {{ $period->formatted }}</h2>
            <p>@money($total) from {{ $payments->count() }} {{ str()->plural('payment', $payments->count()) }}</p>
            @endverbatim
        </x-docs::code>
        <x-docs::code title="app/Http/Controllers/Admins/PaymentReportController.php">
            use App\Models\Payment;
            use Illuminate\Contracts\View\View;
            use Illuminate\Http\Request;
            use Illuminate\Support\Facades\Gate;

            class PaymentReportController
            {
                public function __invoke(Request $request): View
                {
                    Gate::authorize('viewAny', Payment::class);

                    //  Read the period once, defaulting to the current month
                    $period = $request->period();

                    //  Query the whole period, including the last day
                    $payments = Payment::query()
                        ->whereBetween('paid_at', [$period->from, $period->to])
                        ->latest('id')
                        ->get();

                    return view('admins.pages.reports.payments', [
                        'period' => $period,
                        'payments' => $payments,
                        'total' => $payments->sum('amount'),
                    ]);
                }
            }
        </x-docs::code>

        <h3>Using the parts</h3>
        <x-docs::code>
            use App\Models\Payment;

            $period = $request->period('year');        // ?period=2026-03 still gives that month
            $period = $request->period();              // no input gives the current month
            $period = $request->period('day');         // no input gives today

            //  Compare with the same period a year earlier, without changing $period
            $lastYear = [$period->from->copy()->subYear(), $period->to->copy()->subYear()];

            //  Group a year by month, using the year the user chose
            $period = $request->period('year');
            Payment::query()
                ->whereYear('paid_at', $period->year)
                ->get()
                ->groupBy(fn (Payment $payment) => $payment->paid_at->format('M'));

            //  A heading that adapts: a month shows its name, anything else shows the text
            $heading = $period->month ? $period->date->format('F Y') : $period->formatted;
        </x-docs::code>
        <x-docs::callout type="warning" title="Copy before you change a date">
            <code>from</code> and <code>to</code> are mutable Carbon dates. Call <code>copy()</code> before <code>subYear()</code>, <code>addDay()</code> or any other change, or you alter the period you still need.
        </x-docs::callout>

        <h3>What happens to bad input</h3>
        <x-docs::table :columns="['?period=', 'Result']" :rows="[
            ['2026-03', 'March 2026.'],
            ['2026-13', 'Not a real month, so the default period.'],
            ['2026-02-31', 'Not a real day, so the default period.'],
            ['2026-03-31 to 2026-03-01', 'Ends before it starts, so the default period.'],
            ['2026-01 to 2026-03-31', 'The ends are different kinds, so the default period.'],
            ['last week', 'Not a supported format, so the default period.'],
            ['(missing or empty)', 'The default period.'],
        ]" />
        <p>The page never errors on a bad filter. It shows the default period instead, and <code>$period->formatted</code> tells the user which one they got.</p>
    </x-docs::section>
@endsection
