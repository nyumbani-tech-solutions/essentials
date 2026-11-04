@extends('docs::layout')

@section('page', 'Messaging')
@section('lead', 'SMS and email for every Nyumbani app: build a message for a scope once and let the package queue, limit and retry it.')

@section('content')
    <x-docs::section id="what" title="What it is">
        <p><code>nyumbani/messaging</code> sends SMS through Nyumbani Connect and email through ZeptoMail, so an app never talks to a provider. It is optional, for apps that send SMS: choose it in <code>php artisan nyumbani:install</code>, or run <code>composer require nyumbani/messaging</code>. Nyumbani Connect itself, which receives the messages, does not use it. It has its own docs, because it needs only Laravel and can be used on its own.</p>
        @if ($packages->has('nyumbani-messaging'))
            <x-docs::card title="Open the Messaging docs" href="{{ route('docs.page', ['package' => 'nyumbani-messaging']) }}">Installation, a walkthrough, scopes and identity, writing and sending messages, email, the channel switches, and how to test and troubleshoot.</x-docs::card>
        @else
            <x-docs::callout type="note" title="Not installed">Install the package to read its own docs here: see its README, or the installation steps above.</x-docs::callout>
        @endif
    </x-docs::section>

    <x-docs::section id="offers" title="What it gives you">
        <x-docs::table :columns="['Piece', 'Use it to']" :rows="[
            ['MessageScope', 'Make the model every message belongs to (a project, an organisation) decide which channels are on and who the message is from.'],
            ['Message', 'Build one message for a scope: recipient, salutation, body, subject and channels.'],
            ['MessageHandler', 'Keep the wording of one kind of message in one class.'],
            ['send(), sendTo()', 'Queue one message, or a copy for each of many recipients, on SMS, email or both.'],
            ['Messenger', 'Ask whether a channel is on for a project, and start messages for it.'],
            ['SendMessageJob', 'Deliver to Connect or ZeptoMail, rate limited and retried.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="taste" title="A first taste">
        <x-docs::code>
            message()
                ->scope($project)
                ->recipient($user)
                ->salutation()
                ->text('Your rent is due on Friday.')
                ->send('rent');
        </x-docs::code>
        <p><code>$project</code> is the model that owns the message and implements <code>MessageScope</code>, and the recipient is a model with a <code>phone_number</code>. A queue worker must be running, because nothing is sent until it picks the job up. The Messaging docs let you build an SMS and see exactly what Connect would receive.</p>
    </x-docs::section>

    <x-docs::section id="agents" title="For AI agents">
        <p>The skill <code>nyumbani-messaging</code> and the shipped messaging rule load with the rest of Essentials. See <a href="{{ $docs->url('rules-and-skills') }}">Rules and skills</a>.</p>
    </x-docs::section>
@endsection
