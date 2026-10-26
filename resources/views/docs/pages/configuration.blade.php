@extends('docs::layout')

@section('page', 'Configuration')
@section('lead', 'Sensible defaults for every app, changed from one config file or the environment.')

@section('content')
    <x-docs::section id="publish" title="Publish the config">
        <p>Apps inherit the defaults, and <code>php artisan nyumbani:install</code> publishes the file for you. Edit it only to change one. To publish it again by hand:</p>
        <x-docs::code lang="bash" title="Terminal">
            php artisan vendor:publish --tag=essentials-config
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="options" title="Options">
        <x-docs::table :columns="['Key', 'Default', 'What it does']" :rows="[
            ['currency', 'KES', 'The symbol money() and currency() use. Set with ESSENTIALS_CURRENCY.'],
            ['fortify.max_attempts', '5', 'Sign-in attempts per minute for each username and IP address.'],
            ['defaults.*', 'true', 'Switches for the app-wide defaults. See App defaults.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="env" title="Environment variables">
        <x-docs::code lang="bash" title=".env">
            ESSENTIALS_CURRENCY=KES
        </x-docs::code>
        <x-docs::callout type="warning" title="Keep secrets out of .env.example">
            Credentials, webhook URLs and keys live only in the git-ignored <code>.env</code>. <code>.env.example</code> lists every key with a blank value.
        </x-docs::callout>
    </x-docs::section>
@endsection
