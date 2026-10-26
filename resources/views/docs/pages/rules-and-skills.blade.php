@extends('docs::layout')

@section('page', 'Rules and skills')
@section('lead', 'The conventions of Nyumbani, written down for people and for AI agents.')

@section('content')
    <x-docs::section id="rules" title="Rules">
        <p>Rules are short, path-scoped statements such as how controllers or views are written. The package ships them and copies them into the app. The view, JavaScript and Livewire rules are copied only when the app uses the UI package.</p>
        <x-docs::code lang="bash" title="Terminal">
            php artisan nyumbani:install                    # first time, or any time a package changes
            php artisan nyumbani:install --no-interaction   # the same, never asking (what Composer runs)
        </x-docs::code>
        <p>One command does it all, and it is safe to run again (see <a href="{{ $docs->url('installation') }}#install">Installation</a> for everything else it sets up): it always copies the current rules over the old ones, removes rules the package no longer ships, and refreshes Boost. They land in <code>.ai/rules/nyumbani-*.md</code>, and the index in <code>.ai/rules/index.md</code> maps each path to its rule. Do not edit them: the next run replaces them. The Composer <code>post-update-cmd</code> of a Nyumbani app runs it for you, so a <code>composer update</code> brings the newest rules and skills.</p>
    </x-docs::section>

    <x-docs::section id="skills" title="Skills">
        <p>Skills teach an AI agent how to use a part of the package. Laravel Boost finds them in the package's <code>resources/boost/skills</code> folder.</p>
        <p>You do not run Boost yourself. The first time, <code>nyumbani:install</code> runs <code>boost:install</code> and asks Boost's own questions about agents (a non-interactive run turns guidelines, skills and MCP all on). Boost reads skills only from packages your app requires itself, so the installer first makes sure the optional packages you chose (<code>nyumbani/ui</code>, <code>nyumbani/daraja</code>, <code>nyumbani/messaging</code> or <code>nyumbani/dt-tables</code>) are in your <code>composer.json</code> next to Essentials. Every time, it adds <code>nyumbani/essentials</code> and those packages to <code>boost.json</code>, lists their skills there (<code>boost:update</code> installs only the skills named in <code>boost.json</code>), uses Claude Code as the agent when none is chosen, and runs <code>boost:update</code>, so rules, guidelines and skills stay current with one command.</p>
        <x-docs::table :columns="['Skill', 'Covers']" :rows="[
            ['nyumbani-logging', 'The discord log driver, its options and why records do not arrive.'],
            ['nyumbani-install', 'Running nyumbani:install, its setup checklist and how to fix each item.'],
            ['nyumbani-auth', 'Sign-in, password reset, home routes, the login throttle and impersonation.'],
            ['nyumbani-helpers', 'Formatting helpers in PHP, Blade and JavaScript, and the period filter.'],
            ['nyumbani-permissions', 'permissions.json, the sync command, assigning roles, policies and the role list.'],
            ['nyumbani-model-traits', 'Ulids, number identifiers, money, enum labels, uploads, profile images and Kenyan factory data.'],
            ['nyumbani-validation', 'Form Requests, the phone rules, the CleanName cast and password rules.'],
            ['nyumbani-defaults', 'The app-wide defaults, how to switch one off and what each one changes.'],
        ]" />
        <p>The optional Nyumbani packages you choose in the installer ship their own skills the same way, and the installer lists them in <code>boost.json</code> for you. <code>nyumbani/ui</code> adds <code>nyumbani-ui-install</code>, <code>nyumbani-ui-theme</code>, <code>nyumbani-branding</code>, <code>nyumbani-modal</code>, <code>nyumbani-error-pages</code> and <code>nyumbani-error-bags</code>; <code>nyumbani/daraja</code> adds <code>nyumbani-daraja-payments</code> and <code>nyumbani-daraja-webhooks</code>; <code>nyumbani/messaging</code> adds <code>nyumbani-messaging</code>; <code>nyumbani/dt-tables</code> adds <code>dt-tables-development</code>, <code>dt-tables-server-side</code> and <code>dt-tables-admin</code>. Add each package to <code>boost.json</code> and run <code>php artisan boost:update</code>. Their docs are in the switcher at the top of this page.</p>
    </x-docs::section>

    <x-docs::section id="read" title="Read the rules">
        <p>The rules are written out, with examples, on the pages that follow.</p>
        <ul>
            <li><a href="{{ $docs->url('code-style') }}">Code style</a>: doc blocks, comments, names, imports and tests.</li>
            <li><a href="{{ $docs->url('app-code') }}">Application code</a>: services, helpers, providers and the Laravel idioms.</li>
            <li><a href="{{ $docs->url('http-layer') }}">HTTP layer</a>: routes, controllers, Form Requests, policies and language files.</li>
            <li><a href="{{ $docs->url('data-layer') }}">Models and data</a>: models, migrations, enums, seeders and data tables.</li>
            <li><x-docs::package-link package="nyumbani-ui" page="view-layer">Views and scripts</x-docs::package-link>: layouts, Blade, forms, Alpine, Livewire and design, in the UI package.</li>
        </ul>
    </x-docs::section>

    <x-docs::section id="own" title="Your own project skills">
        <p>Project-specific skills go in <code>.ai/skills</code>, not <code>.claude/skills</code>. Anything that should bind every Nyumbani app belongs in the package so every app inherits it.</p>
    </x-docs::section>
@endsection
