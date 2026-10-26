@extends('docs::layout')

@section('page', 'Daraja')
@section('lead', 'M-Pesa for every Nyumbani app: take payments through Safaricom Daraja and know when they land.')

@section('content')
    <x-docs::section id="what" title="What it is">
        <p><code>nyumbani/daraja</code> is a small client for Safaricom's Daraja API. It is optional: choose it in <code>php artisan nyumbani:install</code>, or run <code>composer require nyumbani/daraja</code>. It has its own docs, because it needs only Laravel and can be used on its own.</p>
        @if ($packages->has('nyumbani-daraja'))
            <x-docs::card title="Open the Daraja docs" href="{{ route('docs.page', ['package' => 'nyumbani-daraja']) }}">Installation, a walkthrough of a first payment, every call, and the callbacks Safaricom sends.</x-docs::card>
        @else
            <x-docs::callout type="note" title="Not installed">Install the package to read its own docs here: see its README, or the installation steps above.</x-docs::callout>
        @endif
    </x-docs::section>

    <x-docs::section id="offers" title="What it gives you">
        <x-docs::table :columns="['Piece', 'Use it to']" :rows="[
            ['Daraja::stkPush()', 'Prompt a customer to pay on their phone.'],
            ['Daraja::registerC2BUrls()', 'Hear about every payment made to your paybill or till.'],
            ['Daraja::pullTransactions()', 'Fetch the payments a callback may have missed.'],
            ['EnsureRequestIsFromSafaricom', 'Let only Safaricom reach your callback routes.'],
            ['daraja_phone()', 'Turn any Kenyan number into the 254XXXXXXXXX format Daraja expects.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="taste" title="A first taste">
        <x-docs::code>
            use Nyumbani\Daraja\Daraja;
            use Nyumbani\Daraja\ShortCode;

            $shortCode = new ShortCode('174379', $key, $secret, passKey: $passKey);

            Daraja::for($shortCode)->sandbox()->stkPush('0712345678', 100, 'INV-1', 'Order payment', route('hooks.daraja.stk'));
        </x-docs::code>
        <p>Credentials never go in config: you build a <code>ShortCode</code> wherever your app keeps them, so one app can serve several paybills. The Daraja docs explain the rest, with live examples of every request.</p>
    </x-docs::section>

    <x-docs::section id="agents" title="For AI agents">
        <p>The skills <code>nyumbani-daraja-payments</code> and <code>nyumbani-daraja-webhooks</code> load through Boost with the rest of Essentials. See <a href="{{ $docs->url('rules-and-skills') }}">Rules and skills</a>.</p>
    </x-docs::section>
@endsection
