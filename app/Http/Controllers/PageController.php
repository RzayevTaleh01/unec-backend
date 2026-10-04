<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;

class PageController extends Controller
{
    /** Each former tab is its own page: /{section}/{slug}. */
    public function first(string $section)
    {
        $first = $this->section($section)->pages()->published()->firstOrFail();

        return redirect()->route('pages.show', [$section, $first->slug]);
    }

    public function show(string $section, string $slug)
    {
        $section = $this->section($section);
        $page = $section->pages()->published()->where('slug', $slug)->firstOrFail();

        $section->setRelation('pages', $section->pages()->published()->get());

        return view('pages.show', compact('section', 'page'));
    }

    private function section(string $key): PageSection
    {
        return PageSection::where('key', $key)->firstOrFail();
    }
}
