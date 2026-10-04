<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.api-key', ['hasToken' => $request->user()->tokens()->exists()]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // One active key per user: creating a new one revokes the previous.
        $user->tokens()->delete();
        $token = $user->createToken('profile')->plainTextToken;

        return back()->with('api_token', $token);
    }

    public function destroy(Request $request)
    {
        $request->user()->tokens()->delete();

        return back()->with('status', __('site.profile_page.saved'));
    }
}
