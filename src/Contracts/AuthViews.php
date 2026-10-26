<?php

namespace Nyumbani\Essentials\Contracts;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

interface AuthViews
{
    /**
     * Get the sign-in page.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function loginView(): View;

    /**
     * Get the page that asks for the e-mail to send the reset link to.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function forgotPasswordView(): View;

    /**
     * Get the page that sets the new password, which reads the token and e-mail from the request.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function resetPasswordView(Request $request): View;
}
