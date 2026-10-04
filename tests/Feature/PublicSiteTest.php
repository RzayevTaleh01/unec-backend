<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\Issue;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_every_public_page_renders(): void
    {
        $issue = Issue::latestFirst()->first();
        $article = Article::first();
        $announcement = Announcement::first();

        $urls = [
            '/', '/archive', "/archive/{$issue->id}", "/articles/{$article->id}", '/announcements',
            "/announcements/{$announcement->slug}", '/search', '/editorial-board', '/contact',
            '/privacy', '/terms', '/open-journal-systems', '/login', '/register/personal', '/forgot-password',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_each_former_tab_is_its_own_page(): void
    {
        $expected = [
            'about' => ['purpose', 'scope', 'languages', 'frequency', 'privacy-notice'],
            'submission' => ['author-guide', 'checklist', 'articles', 'how-to-submit', 'ethics', 'copyright', 'privacy-statement'],
            'information' => ['readers', 'authors', 'librarians'],
        ];

        foreach ($expected as $section => $slugs) {
            $this->get("/{$section}")->assertRedirect("/{$section}/{$slugs[0]}");

            foreach ($slugs as $slug) {
                $this->get("/{$section}/{$slug}")->assertOk()->assertSee('content-nav', false);
            }
        }

        $this->get('/about/unknown')->assertNotFound();
    }

    public function test_unpublished_page_is_hidden(): void
    {
        \App\Models\Page::where('slug', 'scope')->update(['is_published' => false]);

        $this->get('/about/scope')->assertNotFound();
        $this->get('/about/purpose')->assertOk()->assertDontSee('/about/scope');
    }

    public function test_announcement_views_are_counted_once_per_session(): void
    {
        $announcement = Announcement::first();
        $before = $announcement->views_count;

        $this->get("/announcements/{$announcement->slug}");
        $this->get("/announcements/{$announcement->slug}");

        $this->assertSame($before + 1, $announcement->fresh()->views_count);
    }

    public function test_future_announcement_is_not_visible(): void
    {
        $announcement = Announcement::first();
        $announcement->update(['published_at' => now()->addDay()]);

        $this->get("/announcements/{$announcement->slug}")->assertNotFound();
    }

    public function test_search_filters_by_title_author_and_dates(): void
    {
        $this->get('/search?title=KOBİ')->assertOk()->assertSee('KOBİ', false)->assertDontSee('POST-MÜNAQİŞƏ');
        $this->get('/search?author=Fəridə')->assertOk()->assertSee('POST-MÜNAQİŞƏ', false);
        $this->get('/search?from=2025-01-01&to=2025-12-31')->assertOk()->assertSee('KOBİ', false)->assertDontSee('POST-MÜNAQİŞƏ');
        $this->get('/search?title=zzzznothing')->assertOk()->assertSee(__('site.search_page.empty'));
        $this->get('/search?from=2025-12-31&to=2025-01-01')->assertSessionHasErrors('to');
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $this->get('/search?title=%25')->assertOk()->assertSee(__('site.search_page.empty'));
    }

    public function test_citation_styles(): void
    {
        $article = Article::first();

        foreach (['apa', 'mla', 'chicago'] as $style) {
            $this->get("/articles/{$article->id}?style={$style}")->assertOk()->assertSee($article->authors->first()->name);
        }
    }

    public function test_language_switch_translates_ui_and_issue_label(): void
    {
        $this->from('/')->get('/lang/en')->assertRedirect('/');
        $this->get('/')->assertSee('Vol. 3 No. 1 (2026)')->assertSee('Archive');

        $this->get('/lang/ru')->assertRedirect();
        $this->get('/')->assertSee('Архив');

        $this->get('/lang/xx')->assertNotFound();
    }

    public function test_untranslated_content_falls_back_to_azerbaijani(): void
    {
        $this->get('/lang/en');

        $this->get('/about/purpose')->assertOk()->assertSee('Tələbə Elmi Cəmiyyəti');
    }

    public function test_admin_html_is_sanitised_on_output(): void
    {
        \App\Models\Page::where('slug', 'purpose')->update([
            'body' => ['az' => '<p onclick="x()">ok</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>'],
        ]);

        $this->get('/about/purpose')
            ->assertOk()
            ->assertDontSee('alert(1)</', false)
            ->assertDontSee('onclick="x()"', false)
            ->assertDontSee('javascript:alert', false);
    }
}
