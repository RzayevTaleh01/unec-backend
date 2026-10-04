<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageSectionResource\Pages\CreatePageSection;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);

        $this->admin = User::factory()->create(['username' => 'adm', 'first_name' => 'Ad', 'last_name' => 'Min']);
        $this->admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($this->admin);
    }

    public function test_every_admin_screen_renders(): void
    {
        $paths = [
            '', '/pages', '/pages/create', '/page-sections', '/page-sections/create', '/issues', '/issues/create',
            '/articles', '/articles/create', '/announcements', '/announcements/create', '/editorial-members',
            '/editorial-members/create', '/contacts', '/contacts/create', '/journals', '/journals/create',
            '/users', '/site-settings',
            '/pages/'.Page::first()->id.'/edit', '/issues/'.Issue::first()->id.'/edit',
            '/articles/'.Article::first()->id.'/edit', '/announcements/'.Announcement::first()->id.'/edit',
        ];

        foreach ($paths as $path) {
            $this->assertSame(200, $this->get('/admin'.$path)->status(), "Admin path [{$path}] did not render");
        }
    }

    public function test_admin_can_publish_an_announcement_that_appears_on_the_site(): void
    {
        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Yeni elan başlığı', 'published_at' => now()->toDateString(), 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::latest('id')->first();
        $this->assertSame('Yeni elan başlığı', $announcement->getTranslation('title', 'az'));

        $this->get('/announcements')->assertSee('Yeni elan başlığı');
    }

    public function test_admin_can_add_a_page_to_a_section_and_it_shows_in_the_tab_menu(): void
    {
        $section = PageSection::where('key', 'about')->first();

        Livewire::test(CreatePage::class)
            ->fillForm(['page_section_id' => $section->id, 'title' => 'Yeni tab', 'slug' => 'new-tab', 'body' => '<p>Mətn</p>', 'sort_order' => 9, 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/about/new-tab')->assertOk()->assertSee('Yeni tab');
        $this->get('/about/purpose')->assertSee('/about/new-tab');
    }

    public function test_page_slug_must_be_unique_within_its_section(): void
    {
        $section = PageSection::where('key', 'about')->first();

        Livewire::test(CreatePage::class)
            ->fillForm(['page_section_id' => $section->id, 'title' => 'Dup', 'slug' => 'purpose'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_translations_are_saved_per_locale(): void
    {
        $page = Page::where('slug', 'purpose')->first();

        Livewire::test(EditPage::class, ['record' => $page->id])
            ->set('activeLocale', 'en')
            ->fillForm(['title' => 'Purpose', 'body' => '<p>English text</p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $this->assertSame('Purpose', $page->getTranslation('title', 'en'));
        $this->assertSame('Məqsəd', $page->getTranslation('title', 'az'));

        $this->get('/lang/en');
        $this->get('/about/purpose')->assertSee('English text', false);
    }

    public function test_new_section_cannot_shadow_application_routes(): void
    {
        Livewire::test(CreatePageSection::class)
            ->fillForm(['title' => 'X', 'key' => 'login'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        Livewire::test(CreatePageSection::class)
            ->fillForm(['title' => 'Qaydalar', 'key' => 'rules', 'sort_order' => 5])
            ->call('create')
            ->assertHasNoFormErrors();

        Page::create(['page_section_id' => PageSection::where('key', 'rules')->first()->id, 'slug' => 'intro', 'title' => ['az' => 'Giriş'], 'body' => ['az' => '<p>x</p>']]);

        $this->get('/rules')->assertRedirect('/rules/intro');
        $this->get('/rules/intro')->assertOk();
        // Built-in routes still win over the dynamic section route.
        $this->get('/archive')->assertOk();
        $this->get('/admin/login')->assertRedirect('/admin');
    }

    public function test_article_needs_at_least_one_author(): void
    {
        Livewire::test(CreateArticle::class)
            ->fillForm(['title' => 'Başlıq', 'status' => 'published', 'language' => 'az', 'authors' => []])
            ->call('create')
            ->assertHasFormErrors(['authors']);
    }

    public function test_admin_flag_is_managed_explicitly_and_self_lockout_is_prevented(): void
    {
        $user = User::factory()->create(['username' => 'u1', 'first_name' => 'U', 'last_name' => 'One']);
        $this->assertFalse((bool) $user->is_admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['first_name' => 'U', 'username' => 'u1', 'email' => $user->email, 'is_admin' => true])
            ->call('save')->assertHasNoFormErrors();
        $this->assertTrue($user->fresh()->is_admin);

        Livewire::test(EditUser::class, ['record' => $this->admin->id])
            ->fillForm(['first_name' => 'Ad', 'username' => 'adm', 'email' => $this->admin->email, 'is_admin' => false])
            ->call('save')->assertHasNoFormErrors();
        $this->assertTrue($this->admin->fresh()->is_admin);
    }

    public function test_site_settings_override_defaults_and_fall_back(): void
    {
        Livewire::test(SiteSettings::class)
            ->fillForm(['hero_title' => ['az' => 'Mənim başlığım', 'en' => ''], 'systems_url' => 'https://example.org/ojs'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Mənim başlığım', Setting::get('hero_title'));

        $this->get('/')->assertSee('Mənim başlığım')->assertSee('https://example.org/ojs', false);

        // English has no override: it falls back to the Azerbaijani value rather than showing nothing.
        $this->get('/lang/en');
        $this->get('/')->assertSee('Mənim başlığım');
    }
}
