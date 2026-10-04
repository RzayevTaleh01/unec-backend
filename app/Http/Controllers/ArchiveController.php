<?php

namespace App\Http\Controllers;

use App\Models\Issue;

class ArchiveController extends Controller
{
    public function index()
    {
        return view('archive.index', [
            'issues' => Issue::published()->latestFirst()->paginate(12),
        ]);
    }

    public function show(Issue $issue)
    {
        abort_unless($issue->is_published, 404);

        $issue->load(['articles' => fn ($q) => $q->published(), 'articles.authors', 'articles.issue']);

        return view('archive.show', compact('issue'));
    }
}
