<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationAndAssetsTest extends TestCase
{
    use RefreshDatabase;

    /** A raw translation key leaking into the page, e.g. "site.nav.home" or "validation.required". */
    private const RAW_KEY = '/(?<![\w@.\/-])(site|validation|passwords|auth|pagination)\.[a-z_*]+(\.[a-z_*0-9]+)*(?![\w@\/-])(?=[^<>]*(<|$))/';

    private function assertNoRawKeys(string $html, string $url, string $locale): void
    {
        // Ignore script/style bodies and markup attributes; look at visible text only.
        $text = preg_replace(['#<(script|style)\b.*?</\1>#is', '#<[^>]+>#'], ' ', $html);

        preg_match_all(self::RAW_KEY, $text, $matches);
        $this->assertSame([], array_unique($matches[0]), "Untranslated keys on [{$url}] in [{$locale}]");
    }

    public function test_no_page_leaks_raw_translation_keys_in_any_locale(): void
    {
        $this->seed(ContentSeeder::class);
        $author = User::factory()->create(['first_name' => 'A', 'last_name' => 'B', 'username' => 'a', 'roles' => ['reader', 'author']]);
        $reviewer = User::factory()->create(['first_name' => 'R', 'last_name' => 'V', 'username' => 'r', 'roles' => ['reader', 'reviewer']]);
        $admin = User::factory()->create(['username' => 'adm']);
        $admin->forceFill(['is_admin' => true])->save();

        $workflow = app(SubmissionWorkflow::class);
        $article = Article::create(['submitter_id' => $author->id, 'title' => ['en' => 'Title'], 'abstract' => ['en' => str_repeat('x', 60)], 'keywords' => ['en' => 'k'], 'language' => 'en', 'status' => 'draft']);
        $article->authors()->create(['name' => 'A B', 'sort_order' => 0]);
        $article->files()->create(['uploaded_by' => $author->id, 'path' => 'articles/x/p.docx', 'original_name' => 'p.docx', 'size' => 10]);
        $draft = Article::create(['submitter_id' => $author->id, 'title' => ['az' => ''], 'status' => 'draft']);
        $workflow->submit($article);
        $review = $workflow->assignReviewer($article, $reviewer, $admin, now()->addWeek());

        $guestUrls = ['/', '/archive', '/archive/1', '/articles/1', '/announcements', '/search', '/search?title=zzz', '/editorial-board', '/contact',
            '/about/purpose', '/submission/ethics', '/information/readers', '/privacy', '/login', '/register/personal', '/register/success', '/forgot-password', '/forgot-password/sent'];
        $authorUrls = ['/profile/identity', '/profile/contact', '/profile/roles', '/profile/roles/journals', '/profile/public', '/profile/password',
            '/profile/notifications', '/profile/api-key', '/profile/submissions', "/profile/submissions/{$article->id}", '/profile/inbox', '/submit',
            "/submit/{$draft->id}"];
        $reviewerUrls = ['/profile/reviews', "/profile/reviews/{$review->id}"];

        foreach (['az', 'en', 'ru'] as $locale) {
            foreach ([[null, $guestUrls], [$author, $authorUrls], [$reviewer, $reviewerUrls]] as [$user, $urls]) {
                $user ? $this->actingAs($user) : auth()->logout();
                $this->withSession(['locale' => $locale]);

                foreach ($urls as $url) {
                    $response = $this->get($url)->assertOk();
                    $this->assertNoRawKeys($response->getContent(), $url, $locale);
                }
            }
        }
    }

    public function test_validation_messages_are_translated(): void
    {
        foreach (['az', 'en', 'ru'] as $locale) {
            $this->withSession(['locale' => $locale])
                ->from('/register/personal')
                ->post('/register/personal', ['country' => 'zz'])
                ->assertSessionHasErrors(['first_name', 'last_name', 'institution', 'country']);

            foreach (session('errors')->all() as $message) {
                $this->assertStringNotContainsString('validation.', $message, "Locale [{$locale}]");
            }
        }
    }

    public function test_admin_panel_ships_the_livewire_runtime(): void
    {
        $admin = User::factory()->create(['username' => 'adm']);
        $admin->forceFill(['is_admin' => true])->save();

        // Without the Livewire script every admin screen is a blank page.
        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#livewire(\.min)?\.js#', $html);
    }

    public function test_search_page_exposes_month_names_for_the_date_picker(): void
    {
        $this->seed(ContentSeeder::class);

        $html = $this->get('/search')->assertOk()->getContent();
        $this->assertStringContainsString('Yanvar', html_entity_decode($html));
    }

    public function test_pages_render_with_an_empty_database(): void
    {
        $this->withoutDeprecationHandling();

        foreach (['/', '/archive', '/announcements', '/search', '/search?title=x', '/editorial-board', '/contact', '/login', '/register/personal'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/about')->assertNotFound();
        $this->get('/privacy')->assertNotFound();
    }

    public function test_pages_render_when_optional_fields_are_missing(): void
    {
        $this->withoutDeprecationHandling();

        $issue = \App\Models\Issue::create(['volume' => 9, 'number' => 1, 'year' => 2030, 'is_published' => true]);
        $article = Article::create(['title' => ['az' => 'Yalnız başlıq'], 'status' => 'published']);
        $announcement = \App\Models\Announcement::create(['title' => ['az' => 'Elan'], 'published_at' => now()->subDay()]);
        $member = \App\Models\EditorialMember::create(['name' => 'X', 'role' => ['az' => 'Y']]);

        foreach (['/', '/archive', "/archive/{$issue->id}", "/articles/{$article->id}", '/announcements', "/announcements/{$announcement->slug}", '/editorial-board', '/search'] as $url) {
            $this->get($url)->assertOk();
        }

        // Same article attached to an issue, still without authors/abstract/doi.
        $article->update(['issue_id' => $issue->id]);
        foreach (['apa', 'mla', 'chicago'] as $style) {
            $this->get("/articles/{$article->id}?style={$style}")->assertOk();
        }
    }
}
