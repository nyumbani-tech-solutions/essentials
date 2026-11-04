---
paths:
  - 'app/Services/Messaging/**'
---

# Messaging

## Send through `nyumbani/messaging`, never a provider
Build the message with `message()->scope($project)` and finish with `->send($tags)`; choose channels with `subject()` (adds email) or `via([...])`. Dispatch `Nyumbani\Messaging\Events\MessageProcessed` with an `sms` and/or `email` payload only when you build the items yourself. To send to many recipients use `->sendTo($recipients, $tags)`, never a loop of `send()`. Do not call Connect, Bunicom, ZeptoMail or any provider with `Http` from an app, and do not try to choose or replace a provider: each channel's driver is fixed inside the package.

## One handler per message type
Build each message in a class that extends `Nyumbani\Messaging\MessageHandler`, with its inputs in the constructor and the text in `handle()`. Do not assemble message text in controllers, models or jobs. A variant of one message is another protected method with no required arguments, chosen with `handler($handler, 'reminder')`.

## Give every SMS a Connect category
Set `->category('payment-receipt')` in the handler's `handle()`, so every use of the message type is filed together in Connect. Use short kebab-case keys of at most 100 characters (a longer one throws), and reuse the keys other Nyumbani apps already use, such as `invoice`, `payment-receipt`, `arrears-reminder` and `new-tenant`. The package sends the category exactly as given, so any prefix is your app's own rule.

## Every message belongs to a scope
Every message needs a scope: the model that owns it, such as a project or an organisation, which implements `MessageScope`. A message without one throws, so do not build one with no scope. `Messenger::for($project)` answers `canSend()`, and the message takes its header, footer, sender ID, organization name, logo and email sender and reply-to from the project when it is built. Implement `MessageScope` on the project and return its `MessageIdentity` from `messageIdentity()` (every argument is an optional, named one; an email throws without an organization name, a sender and a logo), so its identity is defined in one place, and do not set those on each message. Check `canSend()` before building messages, and start each message with `$messenger->recipient($model)`.

## The recipient needs a `phone_number`
`Message` reads `phone_number` from the recipient. A column with another name gets a `phoneNumber()` accessor. A number with fewer than nine digits is dropped silently.

## Sync or a worker, both send
On `QUEUE_CONNECTION=sync` the SMS is posted during the request and a Connect failure throws a `RequestException` to the caller, with no retry. On any other connection a queue worker must be running; it retries a failure up to three times. Do not tell a user "nothing is sent" without checking which connection is in use.

## Email: brand the message, not the config
Finish an email with `toEmail($subject)` and dispatch it under `email`. Set the sender, reply-to, logo, organization name and template with `from()`, `replyTo()`, `logo()`, `organization()` and `emailOptions(['template_key' => ...])` on each message, because branding is per message; `messaging.email` only fills what a message leaves out. Email is off until `MESSAGING_EMAIL_ENABLED=true`. The recipient needs an `email` and a `name`; an invalid address is dropped silently.

## Test with fakes, never a provider
Use `Queue::fake()` and `Queue::assertPushed(SendMessageJob::class)` to prove a message was queued, and `Http::fake()` with `(new SendMessageJob(SmsGateway::class, $payload))->handle()` (or `EmailGateway::class` for email) to prove the request. Assert the text with `Message::make()->...->toSMS()['text']` or `->toEmail($subject)['body']`.
