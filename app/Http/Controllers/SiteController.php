<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Contact;
use App\Models\EditorialMember;
use App\Models\Issue;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home()
    {
        $latest = Issue::published()->latestFirst()->with('articles.authors', 'articles.issue')->first();

        return view('home', [
            'stats' => [
                'articles' => Article::count(),
                'issues' => Issue::published()->count(),
            ],
            'latest' => $latest,
            'thumbs' => Issue::published()->latestFirst()->take(3)->get(),
            'archive' => Issue::published()->latestFirst()->take(6)->get(),
            'infoPages' => PageSection::where('key', 'information')->first()?->pages()->published()->get() ?? collect(),
            'announcements' => Announcement::published()->orderByDesc('published_at')->orderByDesc('id')->take(4)->get(),
        ]);
    }

    public function locale(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back(fallback: route('home'));
    }

    public function editorial()
    {
        return view('editorial', ['members' => EditorialMember::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function contact()
    {
        return view('contact', ['contacts' => Contact::orderBy('sort_order')->get()]);
    }

    public function standalone(string $slug)
    {
        $page = Page::published()->whereNull('page_section_id')->where('slug', $slug)->firstOrFail();

        return view('pages.standalone', compact('page'));
    }
}
