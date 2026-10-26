<?php

namespace Nyumbani\Essentials\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Nyumbani\Essentials\Actions\Fortify\ResetUserPassword;
use Nyumbani\Essentials\Contracts\AuthViews;
use Nyumbani\Essentials\Http\Responses\LoginResponse;
use Nyumbani\Essentials\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register the default responses, the login throttle and the password reset feature
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function register(): void
    {
        //  Define the login response
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        //  Define the logout response
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);

        //  Offer password reset only: no registration, verification, 2FA or passkeys
        config(['fortify.features' => [Features::resetPasswords()]]);

        //  Apply the login throttle; Fortify reads it before boot
        config(['fortify.limiters.login' => config('fortify.limiters.login') ?? 'login']);
    }

    /**
     * Set the password rules, reset action, login throttle and auth views
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function boot(): void
    {
        //  Set the default password rules
        Password::defaults(fn () => Password::min(8));

        //  Define the password reset action
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        //  Throttle login attempts per username and IP address
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(config('essentials.fortify.max_attempts', 5))->by(
            str($request->input(Fortify::username()))->lower().'|'.$request->ip()
        ));

        //  Define the auth views, when a theme provides them
        $this->handleAuthViews();
    }

    /**
     * Point Fortify at the views of the bound AuthViews, and answer 404 on those pages when none is bound, which an app's own Fortify views replace
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function handleAuthViews(): void
    {
        //  A headless app has no sign-in pages until it brings its own, so answer 404 instead of failing on views nothing provides
        if (! $this->app->bound(AuthViews::class)) {
            //  Define the login view
            Fortify::loginView(fn () => abort(404));

            //  Define the forgot password view
            Fortify::requestPasswordResetLinkView(fn () => abort(404));

            //  Define the reset password view
            Fortify::resetPasswordView(fn () => abort(404));

            //  Define the register view
            return;
        }

        //  Define the register view
        $views = $this->app->make(AuthViews::class);

        //  Define the login view
        Fortify::loginView(fn () => $views->loginView());

        //  Define the forgot password view
        Fortify::requestPasswordResetLinkView(fn () => $views->forgotPasswordView());

        //  Define the reset password view
        Fortify::resetPasswordView(fn (Request $request) => $views->resetPasswordView($request));
    }
}
