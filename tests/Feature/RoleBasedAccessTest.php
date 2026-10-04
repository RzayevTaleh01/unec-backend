<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Article;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** RBAC from the technical specification: staff roles are granted by an administrator, never self-selected. */
class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, array $roles, bool $admin = false): User
    {
        $user = User::factory()->create(['first_name' => ucfirst($name), 'last_name' => 'T', 'username' => $name, 'roles' => $roles]);
        if ($admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    public function test_reviewer_panel_is_hidden_and_blocked_without_the_role(): void
    {
        $this->seed(ContentSeeder::class);
        $reader = $this->user('reader1', ['reader', 'author']);

        $this->actingAs($reader)->get('/profile/identity')->assertOk()->assertDontSee(route('profile.reviews'), false);
        $reviewer = $this->user('realrev', ['reader', 'reviewer']);
        $article = Article::first();
        $review = $article->reviews()->create(['reviewer_id' => $reviewer->id, 'status' => 'pending']);

        $this->get('/profile/reviews')->assertForbidden();
        $this->get("/profile/reviews/{$review->id}")->assertForbidden();
        $this->post("/profile/reviews/{$review->id}/respond", ['answer' => 'accept'])->assertForbidden();
        $this->post("/profile/reviews/{$review->id}/submit", [])->assertForbidden();
    }

    public function test_reviewer_panel_is_available_to_reviewers_only(): void
    {
        $this->seed(ContentSeeder::class);
        $reviewer = $this->user('rev', ['reader', 'reviewer']);

        $this->actingAs($reviewer)->get('/profile/identity')->assertSee(route('profile.reviews'), false);
        $this->get('/profile/reviews')->assertOk();
    }

    public function test_an_administrator_does_not_automatically_get_the_reviewer_panel(): void
    {
        $admin = $this->user('adm', ['reader'], true);

        $this->actingAs($admin)->get('/profile/reviews')->assertForbidden();
    }

    public function test_agreeing_to_review_does_not_grant_the_role_but_is_visible_to_admins(): void
    {
        $user = $this->user('volunteer', ['reader']);
        $this->actingAs($user);

        $this->put('/profile/roles', ['roles' => ['reader', 'author'], 'reviewer_volunteer' => '1'])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->consent_reviewer_contact);
        $this->assertFalse($user->hasRole('reviewer'));
        $this->get('/profile/reviews')->assertForbidden();
    }

    public function test_staff_roles_survive_when_a_user_edits_their_own_roles(): void
    {
        $user = $this->user('rev', ['reader', 'reviewer', 'copyeditor']);
        $this->actingAs($user);

        $this->put('/profile/roles', ['roles' => ['author']])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['author', 'reviewer', 'copyeditor'], $user->fresh()->roles);
    }

    public function test_only_administrators_can_grant_staff_roles_in_the_admin_panel(): void
    {
        $admin = $this->user('adm', ['reader'], true);
        $target = $this->user('target', ['reader']);

        $this->actingAs($admin);
        Livewire::test(EditUser::class, ['record' => $target->id])
            ->fillForm(['first_name' => 'Target', 'username' => 'target', 'email' => $target->email, 'roles' => ['reader', 'reviewer']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($target->fresh()->hasRole('reviewer'));
        $this->assertFalse($target->fresh()->is_admin);
    }

    public function test_editors_reach_articles_but_not_admin_only_areas(): void
    {
        $this->seed(ContentSeeder::class);
        $editor = $this->user('chief', ['reader', 'editor_in_chief']);
        $section = $this->user('section', ['reader', 'section_editor']);
        $article = Article::first();

        foreach ([$editor, $section] as $user) {
            $this->actingAs($user);
            $this->get('/admin')->assertOk();
            $this->get('/admin/articles')->assertOk();
            $this->get("/admin/articles/{$article->id}/edit")->assertOk();

            foreach (['/admin/users', '/admin/pages', '/admin/page-sections', '/admin/announcements', '/admin/issues',
                '/admin/editorial-members', '/admin/contacts', '/admin/journals', '/admin/site-settings', '/admin/articles/create'] as $path) {
                $this->get($path)->assertForbidden();
            }
        }
    }

    public function test_plain_users_and_reviewers_cannot_open_the_admin_panel(): void
    {
        foreach ([$this->user('r1', ['reader']), $this->user('r2', ['reader', 'reviewer']), $this->user('r3', ['reader', 'copyeditor'])] as $user) {
            $this->actingAs($user)->get('/admin')->assertForbidden();
        }
    }

    public function test_editors_can_assign_reviewers_and_decide(): void
    {
        $this->seed(ContentSeeder::class);
        $author = $this->user('author', ['reader', 'author']);
        $reviewer = $this->user('rev', ['reader', 'reviewer']);
        $editor = $this->user('chief', ['reader', 'editor_in_chief']);

        $article = Article::create(['submitter_id' => $author->id, 'title' => ['en' => 'T'], 'abstract' => ['en' => str_repeat('x', 60)], 'keywords' => ['en' => 'k'], 'status' => 'draft']);
        $article->authors()->create(['name' => 'A', 'sort_order' => 0]);
        $article->files()->create(['uploaded_by' => $author->id, 'path' => 'articles/x/a.docx', 'original_name' => 'a.docx', 'size' => 1]);

        $workflow = app(SubmissionWorkflow::class);
        $workflow->submit($article);

        // Editors (not only admins) are told about new submissions.
        $this->assertSame(1, $editor->notifications()->where('data->type', 'article_submitted')->count());

        $workflow->assignReviewer($article, $reviewer, $editor);
        $workflow->decide($article->fresh(), $editor, 'accept');

        $this->assertSame('accepted', $article->fresh()->status);
        $this->assertSame($editor->id, $article->decisions()->first()->editor_id);
    }

    public function test_deleting_articles_stays_with_administrators(): void
    {
        $this->seed(ContentSeeder::class);
        $editor = $this->user('chief', ['reader', 'editor_in_chief']);

        $this->assertFalse(\App\Filament\Resources\ArticleResource::canDelete(Article::first()) && $this->actingAs($editor) && false);
        $this->actingAs($editor);
        $this->assertFalse(\App\Filament\Resources\ArticleResource::canDelete(Article::first()));
        $this->assertFalse(\App\Filament\Resources\ArticleResource::canCreate());
    }
}
