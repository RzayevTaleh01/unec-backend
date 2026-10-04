<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $filters = [
            'title' => trim($data['title'] ?? ''),
            'author' => trim($data['author'] ?? ''),
            'from' => $data['from'] ?? '',
            'to' => $data['to'] ?? '',
        ];

        $query = Article::published()->with('authors', 'issue');

        if ($filters['title'] !== '') {
            $query->where('title', 'like', '%'.$this->escapeLike($filters['title']).'%');
        }

        if ($filters['author'] !== '') {
            $query->whereHas('authors', fn ($q) => $q->where('name', 'like', '%'.$this->escapeLike($filters['author']).'%'));
        }

        if ($filters['from'] && $filters['to']) {
            $query->whereDate('published_at', '>=', $filters['from'])->whereDate('published_at', '<=', $filters['to']);
        }

        return view('search', [
            'filters' => $filters,
            'searched' => collect($filters)->filter()->isNotEmpty(),
            'articles' => $query->latest('published_at')->latest('id')->paginate(10)->withQueryString(),
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
