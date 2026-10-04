<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/** Signing out of the admin panel ends the one and only session and lands on the public home page. */
class SiteLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->route('home');
    }
}
