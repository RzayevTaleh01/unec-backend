<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        return view('profile.inbox', ['notifications' => $request->user()->notifications()->paginate(20)]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
