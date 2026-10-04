<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('announcements.index', [
            'announcements' => Announcement::published()->orderByDesc('published_at')->orderByDesc('id')->paginate(12),
        ]);
    }

    public function show(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->is_published && $announcement->published_at->lte(now()), 404);

        // Count one view per visitor session.
        $key = 'viewed_announcement_'.$announcement->id;
        if (! $request->session()->has($key)) {
            $announcement->increment('views_count');
            $request->session()->put($key, true);
        }

        return view('announcements.show', compact('announcement'));
    }
}
