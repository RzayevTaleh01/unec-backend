<?php

use App\Models\Article;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Programmatic access with the API key generated on /profile/api-key.
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'username', 'email', 'roles']));

    Route::get('/issues', fn () => Issue::published()->latestFirst()->get()->map(fn (Issue $issue) => [
        'id' => $issue->id,
        'label' => $issue->label,
        'year' => $issue->year,
        'doi' => $issue->doi_url,
        'pdf' => $issue->pdf_url,
    ]));

    Route::get('/articles', fn () => Article::published()->with('authors', 'issue')->latest('published_at')->paginate(20)
        ->through(fn (Article $article) => [
            'id' => $article->id,
            'title' => $article->title,
            'authors' => $article->authors->pluck('name'),
            'issue' => $article->issue?->label,
            'doi' => $article->doi_url,
            'pdf' => $article->pdf_url,
            'published_at' => $article->published_at?->toDateString(),
        ]));
});
