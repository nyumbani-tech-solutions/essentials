@extends('docs::layout')

@section('page', 'Data tables')
@section('lead', 'Searchable, sortable tables for every Nyumbani app: build a table class once and let the package render and serve it.')

@section('content')
    <x-docs::section id="what" title="What it is">
        <p><code>nyumbani/dt-tables</code> renders DataTables from a table class and the <code>&lt;x-data-table&gt;</code> component. It is optional: choose it in <code>php artisan nyumbani:install</code>, or run <code>composer require nyumbani/dt-tables</code>. It has its own docs, because it needs only Laravel and can be used on its own, with or without Essentials and the UI package.</p>
        @if ($packages->has('nyumbani-dt-tables'))
            <x-docs::card title="Open the DtTables docs" href="{{ route('docs.page', ['package' => 'nyumbani-dt-tables']) }}">Installation, a quick start, inline, server-side and API tables, filters, and how to secure and speed them up.</x-docs::card>
        @else
            <x-docs::callout type="note" title="Not installed">Install the package to read its own docs here: see its README, or the installation steps above.</x-docs::callout>
        @endif
    </x-docs::section>

    <x-docs::section id="offers" title="What it gives you">
        <x-docs::table :columns="['Piece', 'Use it to']" :rows="[
            ['BaseTable', 'Load every row up front and let the browser search and sort them. Essentials builds every table this way unless the request says otherwise.'],
            ['ServerSideTable', 'Page, search and sort in your database, one page per request.'],
            ['ApiTable', 'Serve a table from a remote HTTP API that pages, searches and sorts for itself.'],
            ['x-data-table', 'Render a table in a Blade view from a payload or a source.'],
            ['make:data-table', 'Scaffold a table class, with --server-side or --api for the other two kinds.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="taste" title="A first taste">
        <x-docs::code lang="terminal" title="Terminal">
            php artisan make:data-table Customer
        </x-docs::code>
        <x-docs::code lang="blade" title="resources/views/admins/pages/customers.blade.php">
            @verbatim
            <x-data-table id="customers-table" :payload="$payload">
                <thead>
                    <tr>
                        <th dtt-row-index>#</th>
                        <th dtt-title="name">Name</th>
                        <th dtt-actions>Actions</th>
                    </tr>
                </thead>
            </x-data-table>
            @endverbatim
        </x-docs::code>
        <p>Essentials publishes <code>config/dt-tables.php</code> and leaves it on the package's default Bootstrap theme. In an app that also has the UI package, <code>php artisan ui:install</code> sets an untouched copy to the Tailwind theme with the Nyumbani button and input classes. The <a href="{{ $docs->url('data-layer') }}#data-tables">Models and data</a> page has the conventions for the query and the rows.</p>
    </x-docs::section>

    <x-docs::section id="agents" title="For AI agents">
        <p>The skills <code>dt-tables-development</code>, <code>dt-tables-server-side</code> and <code>dt-tables-admin</code>, and the shipped data tables rule, load with the rest of Essentials. See <a href="{{ $docs->url('rules-and-skills') }}">Rules and skills</a>.</p>
    </x-docs::section>
@endsection
