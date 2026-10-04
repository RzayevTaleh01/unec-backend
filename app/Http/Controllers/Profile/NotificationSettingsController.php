<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.notifications', [
            'settings' => $request->user()->notificationSettings->keyBy('type'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $inApp = (array) $request->input('in_app', []);
        $emailOff = (array) $request->input('email_off', []);

        foreach (collect(NotificationSetting::GROUPS)->flatten() as $type) {
            $user->notificationSettings()->updateOrCreate(
                ['type' => $type],
                ['in_app' => isset($inApp[$type]), 'email' => ! isset($emailOff[$type])],
            );
        }

        return back()->with('status', __('site.profile_page.saved'));
    }
}
