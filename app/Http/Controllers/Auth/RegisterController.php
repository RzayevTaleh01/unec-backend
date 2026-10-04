<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    private const SESSION_KEY = 'register.personal';

    public function personal(Request $request)
    {
        return view('auth.register-personal', ['old' => $request->session()->get(self::SESSION_KEY, [])]);
    }

    public function storePersonal(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'institution' => ['required', 'string', 'max:255'],
            'country' => ['required', 'in:az,tr,other'],
        ]);

        $request->session()->put(self::SESSION_KEY, $data);

        return redirect()->route('register.account');
    }

    public function account(Request $request)
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('register.personal');
        }

        return view('auth.register-account');
    }

    public function storeAccount(Request $request)
    {
        $personal = $request->session()->get(self::SESSION_KEY);

        if (! $personal) {
            return redirect()->route('register.personal');
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'consent_privacy' => ['accepted'],
            'consent_news' => ['nullable', 'boolean'],
            'consent_reviewer_contact' => ['nullable', 'boolean'],
        ]);

        $wantsReviewing = $request->boolean('consent_reviewer_contact');

        $user = new User($personal);
        $user->fill([
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $data['password'],
            'consent_news' => $request->boolean('consent_news'),
            'consent_reviewer_contact' => $wantsReviewing,
            // Agreeing to be contacted about reviewing only flags interest; an administrator grants the role.
            'roles' => ['reader'],
        ]);
        $user->save();

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('register.success');
    }

    public function success()
    {
        return view('auth.register-success');
    }
}
