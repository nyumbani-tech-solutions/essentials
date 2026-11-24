---
paths:
  - 'app/Http/Controllers/**'
  - 'routes/**'
---

# Payments

## Take M-Pesa on screen through `StkPushManager` and the payment prompt
Prompt the payer with `Nyumbani\Daraja\StkPushManager::initiate()` and show the result with `<x-nyumbani::payment-prompt>`. Do not call `Daraja::stkPush()` for a payer who is waiting on a screen, do not build your own waiting or failure screen, and do not write your own polling or listener: the prompt follows the push over Reverb, or by polling the check route with `mode="poll"` when the app has no websocket server. Only a paybill works, since a till is refused and the manual instructions are paybill instructions.

## Inject the manager, there is no facade
Take `StkPushManager` through the constructor or the method. Register the "I have paid" resolver once, in a provider's `boot()`, with the static `StkPushManager::resolvePaymentUsing(fn (array $attempt) => ...)`, which works like registering a macro. Build the `ShortCode` where the app keeps its credentials (config, a binding or the database), never in the package config.

## The app starts the push, the prompt only follows it
Start the push from the app's own route, as a plain request, a Livewire action or an ajax call. Validate the number with the phone rules, decide the amount on the server and never read it from the request, and pass `reference:` (such as an invoice number) when the payer already knows it. When `initiate()` answers, render the prompt with `:attempt="$stkPush->contract($attempt)"`. The prompt has no initiate URL and no retry: a new payment is a new push from the app, and its contract is a new prompt.

## The callbacks are the app's routes
Put the STK result and the C2B confirmation on routes outside the auth group, behind `EnsureRequestIsFromSafaricom` and without CSRF, and pass the STK route as `callbackUrl` with `route()`. The STK route calls `handleCallback()`. The C2B route records the payment first, idempotently on `TransID`, then calls `confirmPayment()`. Answer Safaricom `{"ResultCode": "0", "ResultDesc": "Accepted"}` quickly. See the `nyumbani-daraja-webhooks` skill.

## Fulfil from the C2B payment only
The STK result means the payer completed the prompt, not that the money arrived. Mark an order paid from the C2B payment you record in your confirmation route, never from the STK callback and never on the browser's `payment-confirmed` event, which is for the screen only. The package fires no events besides the `StkPushStatusChanged` broadcast. Do not add partial payment handling: a payment below the amount is not confirmed.

## The "I have paid" resolver must be strict
`resolvePaymentUsing()` receives the attempt and returns the transaction id (or `true`) when the app holds a payment for it. Match the reference and the short code, require an amount of at least the attempt's, and ignore a payment older than the attempt. The package already refuses a payment that confirmed another attempt, so one payment can never confirm two, but it cannot tell a stale payment from a fresh one for a reused reference.

## Develop with `DARAJA_STK_SIMULATE`
With `DARAJA_STK_SIMULATE=true`, `Daraja::stkPush()` sends nothing to Safaricom and makes up the accepted answer, so no phone rings. The package registers the simulator routes (`daraja.simulate.*`, 404 unless simulating) and `<x-nyumbani::stk-simulator :$attempt />` shows the buttons that post the STK result and the C2B confirmation to the app's own hooks, so never write those buttons by hand. It throws in production. Pin it to `false` in `phpunit.xml`, so a developer's `.env` never changes a test.

## The broadcast is public and carries no personal data
The channel `stk.{uuid}` is public and the attempt's UUID is its only secret. Never log the UUID, never add a phone, name or receipt to the broadcast, and never use the UUID for anything else. The waiting time comes from `DARAJA_STK_TIMEOUT` through the contract; pass `:timeout` to the prompt only to override it for one page.
