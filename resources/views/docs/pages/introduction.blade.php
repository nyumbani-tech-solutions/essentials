@extends('docs::layout')

@section('page', 'Introduction')
@section('lead', 'One sign-in, one look and one set of conventions for every internal Nyumbani app.')

@section('content')
    <x-docs::section id="what-it-is" title="What Essentials is">
        <p>Essentials is the opinionated foundation of the Nyumbani ecosystem. It does not give you building blocks to arrange as you like. It decides how a Nyumbani app looks and is structured, so a developer who knows one app can work in any other.</p>
        <p>It covers four areas:</p>
        <ol>
            <li><strong>Helpers</strong> for money, numbers, dates and the signed-in user.</li>
            <li><strong>Services, traits and rules</strong> that models and controllers share, plus app-wide defaults.</li>
            <li><strong>Auth and permissions</strong>: Fortify, roles and permissions, and impersonation, built on the packages it standardises, with no UI attached. The look lives in <x-docs::package-link package="nyumbani-ui">nyumbani/ui</x-docs::package-link>.</li>
            <li><strong>AI rules and skills</strong> that keep every contributor, human or not, on the same conventions.</li>
        </ol>
        <x-docs::callout type="tip" title="Use it before you build it">
            Before writing a helper, a trait or a service, check these docs. If Essentials has one, use it. If it is missing, add it to the package so every app gains it.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="path" title="Where to start">
        <x-docs::table :columns="['You are...', 'Read, in this order']" :rows="[
            ['Adding Essentials to an app', 'Installation, then Configuration.'],
            ['Building your first feature', 'Anatomy of a feature, then the page for each piece you meet. With the UI package, its Quick start builds a page end to end.'],
            ['Looking for one thing', 'Use the filter at the top of the sidebar, or the section list below.'],
            ['An AI agent, or working with one', 'Rules and skills, then the Conventions pages.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="explore" title="Explore by concept">
        <div class="not-prose mt-4 grid gap-4 sm:grid-cols-2">
            <x-docs::card title="Installation" href="{{ $docs->url('installation') }}">Add the package and publish the rules.</x-docs::card>
            <x-docs::card title="Helpers" href="{{ $docs->url('helpers') }}">money(), numeric(), format_date() and the Blade directives built on them.</x-docs::card>
            <x-docs::card title="Traits" href="{{ $docs->url('traits') }}">Ulids, uploads, money, number identifiers and more for your models.</x-docs::card>
            @if ($packages->has('nyumbani-ui'))
                <x-docs::card title="UI" href="{{ $packages['nyumbani-ui']->url() }}">Layouts, the modal, toasts, error pages and the theme, in their own package.</x-docs::card>
            @endif
            <x-docs::card title="Authentication" href="{{ $docs->url('authentication') }}">Sign-in, password reset, home routes and impersonation.</x-docs::card>
            <x-docs::card title="Rules and skills" href="{{ $docs->url('rules-and-skills') }}">The conventions the package publishes into your app and your AI agent.</x-docs::card>
        </div>
    </x-docs::section>

    <x-docs::section id="packages" title="The other Nyumbani packages">
        <p>Essentials is the foundation and stands on its own. The other Nyumbani packages are optional: the installer asks which ones an app uses. Each has its own docs, in the package switcher at the top of this page once it is installed, and a short page under Foundation in the sidebar. Daraja, Messaging and DtTables need only Laravel themselves, so they never require Essentials; the UI package requires it.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @if ($packages->has('nyumbani-daraja'))
                <x-docs::card title="Daraja" href="{{ $packages['nyumbani-daraja']->url() }}">M-Pesa through Safaricom: STK push, C2B and Pull transactions, and the guard for their callbacks.</x-docs::card>
            @endif
            @if ($packages->has('nyumbani-messaging'))
                <x-docs::card title="Messaging" href="{{ $packages['nyumbani-messaging']->url() }}">Queue SMS through Nyumbani Connect with handlers, scopes, rate limiting and retries.</x-docs::card>
            @endif
            @if ($packages->has('nyumbani-dt-tables'))
                <x-docs::card title="DtTables" href="{{ $packages['nyumbani-dt-tables']->url() }}">Searchable, sortable tables: inline, server-side and API, with a Tailwind theme.</x-docs::card>
            @endif
        </div>
    </x-docs::section>

    <x-docs::section id="about-docs" title="About these docs">
        <p>These pages are served by the package itself at <code>/{{ $docs->path() }}</code>, and only in the <code>local</code> environment: anywhere else the address answers 404. Every example here is meant to be copied. A <em>Terminal</em> box is a command, a file name above a box says where the code lives, and a boxed note marks a pitfall.</p>
    </x-docs::section>
@endsection
