<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.roles', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(User::SELF_ROLES)],
            'specialty' => ['nullable', 'string', 'max:255'],
            'reviewer_volunteer' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        // Self-service roles come from the form; staff roles can only be changed by an administrator.
        $user->update([
            'roles' => array_values(array_unique([...$data['roles'], ...$user->staffRoles()])),
            'specialty' => $data['specialty'] ?? null,
            'consent_reviewer_contact' => $request->boolean('reviewer_volunteer'),
        ]);

        return back()->with('status', __('site.profile_page.saved'));
    }

    public function journals(Request $request)
    {
        $memberships = $request->user()->journals->mapWithKeys(
            fn (Journal $journal) => [$journal->id => json_decode($journal->pivot->roles, true) ?? []]
        );

        return view('profile.journals', [
            'journals' => Journal::where('is_primary', false)->orderBy('sort_order')->get(),
            'memberships' => $memberships,
        ]);
    }

    public function updateJournals(Request $request)
    {
        $data = $request->validate([
            'journals' => ['nullable', 'array'],
            'journals.*' => ['array'],
            'journals.*.*' => [Rule::in(User::SELF_ROLES)],
        ]);

        $selected = collect($data['journals'] ?? [])
            ->only(Journal::where('is_primary', false)->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->filter();

        $sync = $selected->map(fn ($roles) => ['roles' => json_encode(array_values(array_unique($roles)))])->all();

        $request->user()->journals()->sync($sync);

        return back()->with('status', __('site.profile_page.saved'));
    }
}
