<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function show(Request $request, Article $article)
    {
        abort_unless($article->status === 'published', 404);

        $article->load('authors', 'issue');

        $style = in_array($request->query('style'), ['apa', 'mla', 'chicago'], true) ? $request->query('style') : 'apa';

        $similar = Article::published()
            ->whereKeyNot($article->id)
            ->with('authors', 'issue')
            ->orderByRaw('issue_id = ? desc', [$article->issue_id ?? 0])
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('articles.show', compact('article', 'style', 'similar'));
    }
}
