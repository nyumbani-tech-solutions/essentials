@extends('docs::layout')

@section('page', 'JavaScript')
@section('lead', 'The formatting helpers, in the browser as well as in PHP.')

@section('content')
    <x-docs::section id="helpers" title="The helpers on window">
        <p>Essentials ships one script, <code>vendor/nyumbani/essentials/resources/dist/helpers.js</code>. It adds <code>money()</code>, <code>numeric()</code>, <code>prettify()</code> and the date helpers to <code>window</code>, with the same names and results as the PHP helpers. Pluralize is bundled inside, so an app installs nothing with npm.</p>
        <p>The UI scripts (toasts, the shell, the modal and the rest) live in <x-docs::package-link package="nyumbani-ui" page="scripts">the UI package</x-docs::package-link>.</p>
    </x-docs::section>

    <x-docs::section id="build" title="Changing the script">
        <p>The source is <code>resources/js/helpers.js</code>. When you change it inside the package, run <code>npm run build</code> in the package folder and commit <code>resources/dist</code> with it.</p>
    </x-docs::section>
@endsection
