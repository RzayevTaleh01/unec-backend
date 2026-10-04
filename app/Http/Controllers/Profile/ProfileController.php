<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function identity(Request $request)
    {
        return view('profile.identity', ['user' => $request->user()]);
    }

    public function updateIdentity(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'publish_name' => ['nullable', 'string', 'max:200'],
        ]);

        $request->user()->update($data);

        return back()->with('status', __('site.profile_page.saved'));
    }

    public function contact(Request $request)
    {
        return view('profile.contact', ['user' => $request->user()]);
    }

    public function updateContact(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'institution' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'in:az,tr,other'],
            'signature' => ['nullable', 'string', 'max:5000'],
            'working_languages' => ['nullable', 'array'],
            'working_languages.*' => ['in:az,en,ru'],
        ]);

        $data['signature'] = Html::clean($data['signature'] ?? null) ?: null;
        $data['working_languages'] = $data['working_languages'] ?? [];

        $user->update($data);

        return back()->with('status', __('site.profile_page.saved'));
    }

    public function publicProfile(Request $request)
    {
        return view('profile.public', ['user' => $request->user()]);
    }

    public function updatePublic(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'homepage_url' => ['nullable', 'url:http,https', 'max:255'],
            'orcid' => ['nullable', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
        ]);

        $data['bio'] = Html::clean($data['bio'] ?? null) ?: null;

        if ($request->hasFile('photo')) {
            if ($user->photo && ! str_starts_with($user->photo, 'assets/')) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        } else {
            unset($data['photo']);
        }

        $user->update($data);

        return back()->with('status', __('site.profile_page.saved'));
    }

    public function password()
    {
        return view('profile.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', __('site.profile_page.saved'));
    }
}
