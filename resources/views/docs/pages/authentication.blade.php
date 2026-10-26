@extends('docs::layout')

@section('page', 'Authentication')
@section('lead', 'Sign-in, password reset and impersonation come ready-made. Your app supplies a user model and a home route.')

@section('content')
    <x-docs::section id="what" title="What you get">
        <p>Essentials configures Laravel Fortify for every Nyumbani app, so you never build a login screen.</p>
        <x-docs::table :columns="['Included', 'Details']" :rows="[
            ['Sign-in page', 'Served at the login route, with the app name and the optional branding description. Posts to login.store.'],
            ['Forgot and reset password pages', 'Styled to match, using Fortify\'s own mail link flow.'],
            ['Logout', 'Always redirects to the login page.'],
            ['Login throttle', '5 attempts a minute for each username and IP address. Change it with essentials.fortify.max_attempts.'],
            ['Password rules', 'At least 8 characters. Applied on reset through Password::defaults().'],
            ['Impersonation', 'Sign in as another user and leave again, with a bar on every page while you do.'],
        ]" />
        <x-docs::callout type="note" title="Only password reset">
            Registration, e-mail verification, two-factor authentication and passkeys are switched off. Nyumbani apps are internal: an administrator creates users.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="home" title="Tell the app where each user lands">
        <p>After signing in, a user goes to the page they were trying to reach, or else to their <strong>home route</strong>. Your user model decides it by implementing <code>HasHomeRoute</code>. The logo in the sidebar links there too.</p>
        <x-docs::code title="app/Models/User.php">
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Nyumbani\Essentials\Contracts\HasHomeRoute;

            class User extends Authenticatable implements HasHomeRoute
            {
                public function homeRoute(): string
                {
                    //  Choose by permission, never return null
                    return match (true) {
                        $this->hasPermissionTo('view-dashboard') => route('dashboard'),
                        $this->hasPermissionTo('view-invoices') => route('invoices.index'),
                        default => route('unauthorized'),
                    };
                }
            }
        </x-docs::code>
        <x-docs::callout type="warning" title="Forgetting the contract">
            Outside production, signing in with a user model that does not implement <code>HasHomeRoute</code> throws a <code>LogicException</code> that names the model, so you find out in development. In production it logs a warning and falls back to <code>fortify.home</code>, so a sign-in is never broken.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="routes" title="The routes you can rely on">
        <x-docs::table :columns="['Route name', 'Purpose']" :rows="[
            ['login', 'The sign-in page. The portal layout of the UI package sends guests here and remembers where they were going.'],
            ['login.store', 'Where the sign-in form posts.'],
            ['logout', 'Where the Logout button posts.'],
            ['password.request, password.email', 'Ask for a reset link.'],
            ['password.reset, password.update', 'Choose a new password.'],
            ['essentials.impersonation.leave', 'Ends an impersonation.'],
        ]" />
        <p>Do not redefine them. To change what happens after sign-in or sign-out, override the Fortify response in your own <code>FortifyServiceProvider</code>, for example to send each role to its own area.</p>
    </x-docs::section>

    <x-docs::section id="views" title="Swapping the sign-in pages">
        <p>Fortify renders its three pages through the <code>AuthViews</code> contract: sign-in, forgot password and reset password. Essentials binds its own pages by default. A theme, or your app, replaces them by binding its own implementation in a service provider. When nothing is bound, Fortify gets no views and the app supplies them.</p>
        <x-docs::code title="app/Providers/AppServiceProvider.php">
            use App\Views\BrandedAuthViews;
            use Nyumbani\Essentials\Contracts\AuthViews;

            public function register(): void
            {
                $this->app->singleton(AuthViews::class, BrandedAuthViews::class);
            }
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="username" title="Signing in with something other than e-mail">
        <p>The sign-in form follows <code>config('fortify.username')</code>. Set it to <code>phone_number</code> and the field, label, placeholder and input type change with it, and the throttle counts attempts per phone number.</p>
        <x-docs::code title="config/fortify.php">
            'username' => 'phone_number',
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="impersonation" title="Impersonation">
        <p>Support staff often need to see what a user sees. The package builds on <code>lab404/laravel-impersonate</code>: give the user model its trait and the UI package's portal layout does the rest.</p>
        <x-docs::code title="app/Models/User.php">
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Lab404\Impersonate\Models\Impersonate;
            use Nyumbani\Essentials\Contracts\HasHomeRoute;

            class User extends Authenticatable implements HasHomeRoute
            {
                use Impersonate;
            }
        </x-docs::code>
        <x-docs::code title="Start from a controller or a button">
            use App\Models\User;
            use Illuminate\Http\RedirectResponse;
            use Illuminate\Support\Facades\Gate;

            public function impersonate(User $user): RedirectResponse
            {
                Gate::authorize('impersonate', $user);

                user()->impersonate($user);

                return redirect()->to($user->homeRoute());
            }
        </x-docs::code>
        <p>With the UI package, every portal page shows <em>Impersonating Jane Wanjiru</em> with a <strong>Leave</strong> button. It posts to <code>essentials.impersonation.leave</code>, signs you back in as yourself and sends you to your home route. Decide who may impersonate with a policy, as with any other action.</p>
            <x-docs::example title="What the bar looks like">
            <div class="flex h-9 items-center justify-center gap-x-4 bg-primary-dark px-4 text-center text-xs text-white">
                <span>Impersonating <span class="font-semibold">Jane Wanjiru</span></span>
                <span class="font-medium underline">Leave</span>
            </div>
        </x-docs::example>
    </x-docs::section>

    <x-docs::section id="config" title="Configuration">
        <x-docs::table :columns="['Key', 'Default', 'What it does']" :rows="[
            ['essentials.fortify.max_attempts', '5', 'Sign-in attempts per minute for each username and IP address.'],
        ]" />
    </x-docs::section>
@endsection
