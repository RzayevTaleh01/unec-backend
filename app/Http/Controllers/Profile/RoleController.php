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
        return view('profile.roles', [
            'user' => $request->user(),
            'journals' => Journal::where('is_primary', false)->orderBy('sort_order')->get(),
            'memberships' => $this->memberships($request),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(User::SELF_ROLES)],
            'specialty' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // Self-service roles come from the form; staff roles can only be changed by an administrator.
        $user->update([
            'roles' => array_values(array_unique([...$data['roles'], ...$user->staffRoles()])),
            'specialty' => $data['specialty'] ?? null,
        ]);

        return back()->with('status', __('site.profile_page.saved'));
    }

    /** Old standalone page: the journals now open as a modal on the roles page. */
    public function journals()
    {
        return redirect()->to(route('profile.roles').'#journals');
    }

    /** @return \Illuminate\Support\Collection<int, array<int, string>> journal id => roles */
    private function memberships(Request $request)
    {
        return $request->user()->journals->mapWithKeys(
            fn (Journal $journal) => [$journal->id => json_decode($journal->pivot->roles, true) ?? []]
        );
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
