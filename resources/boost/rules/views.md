---
paths:
  - 'resources/views/**'
---

# Views

## Audience-first folders
Views are grouped by audience: `admins/{pages,partials}`, `system-admins/{pages,partials}`, with shared `layouts` and `components`. Modal components live in `components/{audience}/modal`.

## Pages use the shared layout
Signed-in pages `@extends('nyumbani::layouts.portal')`, and the authentication pages (sign-in, forgot password, reset password, all shipped by the UI package, `nyumbani/ui`) `@extends('nyumbani::layouts.auth')`; both extend the common `nyumbani::layouts.app`. The auth layout is for those pages only: any other public page extends `nyumbani::layouts.app`. A portal page fills its sections: `title`, `sidebar`, `content` (drawn inside a light grey card with a soft shadow that the layout provides, so never wrap a page in an outer card of your own), plus optional `brand`, `navbar`, `user-menu` and `banner`, and `@push('actions')`. The page content section is always called `content`. The portal draws `portal.content` when the app layout defines it, and the page's `content` otherwise: an app layout may define `portal.content` to wrap every page (for example in a white card) and yield `content` inside it, and a page that must skip that wrapper fills another section the layout checks for, such as `content.plain`. The layout sends a guest to login as a safety net; routes are still protected with the `auth` middleware. While a user is impersonated, the layout shows a bar under the page header; its Leave button posts to the package's `essentials.impersonation.leave` route, which returns the user to their home route. Build the sidebar from `<x-nyumbani::nav-link>` and `<x-nyumbani::nav-group>` in the app's own partial, and name that partial once in the app layout with `@section('sidebar', 'partials.sidebar')`; the portal renders it, and throws a `LogicException` when the sidebar is missing or the view does not exist.

## Flash messages are toastr toasts
The shared layout raises each flash message with toastr. The UI package's bundle sets toastr up, so the app installs nothing and does not write its own toast setup.

## The scripts come from the package
Import the UI scripts and the Essentials formatting helpers, one line each: `import '../../vendor/nyumbani/ui/resources/dist/ui.js';` and `import '../../vendor/nyumbani/essentials/resources/dist/helpers.js';`. Never import the files under `resources/js` from an app. Import both before `Livewire.start()`. Livewire provides Alpine and its plugins, so register only your own Alpine plugins, on `alpine:init`.

The sidebar, drawer and user menu are run by the shell script, which uses no Alpine. The double-submit guard is the submit script.

## Formatting helpers and the file input come from the package
The Essentials helpers script defines `money`, `numeric`, `parseNumber`, `pluralize`, `prettify`, `formatDate` and `formatDateTime` on `window`, mirroring the PHP helpers, so do not write your own `$store.helpers`. For file uploads use `<x-nyumbani::file-input>`.

## The modal comes from the package
Use `<x-nyumbani::modal>` for every modal; do not build your own. It runs on the Alpine UI `x-dialog` directive, which the UI bundle registers. Open it with `x-model` (or `wire:model`), the `trigger` slot, or by name: give it `name="edit-customer"` and `$dispatch('open-modal', 'edit-customer')` (`close-modal` closes it; the button that dispatches must sit inside an Alpine scope, so give its wrapper a bare `x-data`), which is the way to open one from a row injected into a data table.

Props: `title`, `size` (`sm`, `md`, `lg`, `xl`, `full`; never a hand-written width), `position="right"` for a slide-over, `error-bag` (reopens it when that named bag has errors after a failed save), `:dismissable="false"` (ignores Escape and outside clicks), `src` (loads the body from that route the first time it opens), `confirm` (a review step before the form posts), `:close-button="false"` and `:show-title="false"`. Fill the `action` slot for the footer, which stays pinned while the body scrolls. On small screens it is a bottom sheet. It dispatches `modal-closed` on close.

## Modal content is always padded
The body of a modal is not padded by the component, so the content you put in it must be. Wrap everything in the default slot in an element with padding of at least `p-4`, and put the class on the form itself for a form (`<form class="p-4">`). A fragment loaded with `src` pads its own root element in the same way. No text, input, table or button may touch the edge of the panel. Only a deliberate full-bleed image or table may skip the padding. The `action` footer is padded by the component.

```blade
<x-nyumbani::modal title="Edit customer" name="edit-customer">
    <form method="POST" action="{{ route('customers.update', ['customer' => $customer]) }}" class="p-4">
        @csrf
        @method('PUT')
        ...
    </form>
</x-nyumbani::modal>
```

## A confirm modal's review is a data-review block in the form
Write the review of a `confirm` modal as a `<div data-review>` inside the form, never as `<x-slot:review>`. The modal copies the block into its review step, so one form partial serves a modal on the page and a form loaded with `src`, and the review is written once. The block reads `data` keyed by field name.

## Reopen a modal after a failed save with an error bag
Name the modal's bag with `error-bag`, post the same name with `@bag('name')` inside its form, read errors with `@error('field', 'name')` (a plain `@error('field')` reads only the default bag), and refill a field from `old()` only when `$errors->hasBag('name')`. Validation needs nothing special. Do not hand-roll a reopen flag.

## No @php in views
Avoid `@php` blocks in views. A component computes values in its class. A page, partial or layout, which has no class, gets its values from the controller (or a view composer for a shared partial). Use `@php` only when there is truly no other place, for example a one-off `@php($i = 0)` counter.

## Favicons come from the package
The shared layout links the Nyumbani favicons, apple-touch icon and web manifest, served by the package. Do not add your own `<link rel="icon">` or favicon files to an app. The manifest takes the app's name and `ui.branding.theme_color`.

## Import classes with @use
Always import a class a view references with `@use('Vendor\Namespace\ClassName')` at the top of the file and use the short name. Never write a fully qualified class name inline in a view.

## Confirm with the browser's confirm()
Ask for confirmation of a destructive action (deleting, voiding) with the native `confirm()` on the form or button: `onsubmit="return confirm('Delete this record?')"`. Do not build a confirmation modal or a custom confirm component for that. To review a form before it saves, use the modal's `confirm` option instead.

## Auth pages come from the package
Login, forgot-password and reset-password are the same in every Nyumbani app and are provided by `nyumbani/essentials`. Do not build your own.

## Anonymous components
Build reusable markup as anonymous `<x-*>` components with `@props()`, as long as the component only prints what it is given. A component that has to work something out (a class list, a width from a size, a default from the session) is a class-based component in `app/View/Components` (`Nyumbani\Essentials\Views\Components` in the package) that computes it in the constructor or a method. Reserve `@include` for layout glue (sidebar, navbar, alerts). A component that needs a short script carries it at the bottom of its file in `@pushOnce('js')`, so it works wherever it is used. A component with a long script keeps it in a file in `resources/js`, registered with `Alpine.data` inside `alpine:init`, which the app imports once.

## No inline PHP
Never write `@php ... @endphp` blocks to compute data. Move it to the controller, a view composer (registered in `ViewServiceProvider`), or the owning DataTable class. Reading `$errors` or `old()` inline is fine. Never inline a fully-qualified class name; add `@use('Vendor\Class')` at the top of the file.

## Plain English copy
Blade view copy is written as plain English text, with no `__()` or `@lang()` wrapping. Only flash messages go through the `alerts.*` translation keys.

## Blade directives for formatting
Use `@date`, `@time`, `@datetime`, `@dateforhumans`, `@numeric`, `@currency`, `@percentage`, `@prettify`, `@bag`, `@money`, `@moneyinwords` and `@numberinwords` instead of raw `format()`, `date()` or `number_format()`. Never write a literal "KES" or "Ksh", even in placeholders (use `e.g. 1000`).

## Blade syntax
Use `@class()` for conditional classes, `@selected` / `@checked` for form state, `:$var` shorthand when names match, self-closing tags for slot-less components, `@js()` to pass PHP data to Alpine, and `@push('js')` for inline scripts. Nest content one level deeper than the enclosing directive. Pluralize with `str()->plural()` and build links with `route()`.

## Every form input has a placeholder
Every form input carries a `placeholder`, never just the label. It is one of three things: a prompt (`Enter your name here`), an example of text that would fit (`e.g. Jane Wanjiru`), or the rule for the input (`At least 8 characters`). Choose the one that helps most; do not repeat the label. The rule covers text, email, tel, password, number and search inputs and every `<textarea>`. A `<select>` gets a first empty option as its placeholder (`<option value="">Select a status</option>`). Checkboxes, radios, file, hidden, date, time and colour inputs have no placeholder to show and are exempt. Placeholders are text for people, so they are translated like any other copy, and they never name a currency (`e.g. 1000`).

## Forms
Required labels carry `aria-required="true"`. Forms never carry a hidden tenant id input.

## Double-submit guard and confirmations
Every submit button is disabled and shows a spinner once its form is submitted, by the package's `submit.js`; do not add your own guard. Opt a button out with `data-allow-resubmit` only when something else runs its busy state (the package has none). Dispatch `reset-loading` on the form to restore a button after an AJAX submit. Destructive actions ask for confirmation, with `x-on:submit="if (! confirm('...')) $event.preventDefault()"` on delete forms. Never call `form.submit()`; use `requestSubmit()`, and never from inside a `submit` listener on the same form.

## Alpine
Keep inline `x-data="{ ... }"` for trivial state only (a couple of keys). Register anything larger with `Alpine.data('name', () => ({ ... }))` inside `document.addEventListener('alpine:init', ...)` in a `@push('js')` block at the bottom of the same file, then use `x-data="name"`. Components are functions taking a `data` param with destructured defaults, use `init()`, `$watch()`, getters, and `x-on:` instead of `@`.

## Design tokens
Import the shared theme from the package in the app's Tailwind css (`@import '../../vendor/nyumbani/ui/resources/css/theme.css';`) and use its primary and secondary colour scales. Do not define your own palette. Every button is primary: use `.btn .btn-primary` from the shared theme (plus `.btn-sm` or `.btn-lg`), never `.btn-secondary` or raw colour utilities. Design mobile-first and meet WCAG AA.

## 1.5 units between cards
Cards sit 1.5 spacing units apart, never more. Space them with `gap-1.5` (or `gap-x-1.5` and `gap-y-1.5`), `m-1.5` (`mx-1.5`, `my-1.5`), `space-x-1.5` and `space-y-1.5`, or `p-1.5` (`px-1.5`, `py-1.5`) on the container, and do not use larger steps such as `gap-4` or `mb-4` between cards. Spacing inside a card, such as its padding and the gap between its fields, is separate and follows the design.

## Name the parameters in route()
Pass route parameters as a keyed array, never a bare model: `route('customers.show', ['customer' => $customer])`, not `route('customers.show', $customer)`. This applies in views, controllers, tests and anywhere else `route()` is called.

## Review a form before saving with the modal's confirm option
When a form needs a review step before it posts, use `<x-nyumbani::modal confirm>` with a `review` slot and a submit in the `action` slot. Do not write hand-rolled two-step toggles with `x-show="confirm"`; they skip native validation or let Enter bypass the review.
