<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The file field used to be an invisible <input> (hidden by the old design CSS): keep it a visible drop zone. */
class FileInputComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_step_renders_a_visible_drop_zone_with_a_working_input(): void
    {
        $user = User::factory()->create(['username' => 'a', 'roles' => ['author']]);
        $article = Article::create(['submitter_id' => $user->id, 'title' => ['az' => ''], 'status' => 'draft']);

        $html = $this->actingAs($user)->get("/submit/{$article->id}")->assertOk()->getContent();

        $this->assertStringContainsString('data-file-drop', $html);
        $this->assertStringContainsString('class="file-drop__zone" for="manuscript"', $html);
        $this->assertStringContainsString('name="manuscript"', $html);
        $this->assertStringContainsString('data-max-mb="20"', $html);
        // The old markup hid the input and offered no way to pick a file.
        $this->assertStringNotContainsString('class="profile-upload"', $html);
    }

    public function test_revision_form_and_profile_photo_use_the_same_component(): void
    {
        $user = User::factory()->create(['username' => 'a', 'roles' => ['author']]);
        $article = Article::create(['submitter_id' => $user->id, 'title' => ['az' => 'T'], 'status' => 'revisions']);

        $this->actingAs($user)->get("/profile/submissions/{$article->id}")->assertOk()
            ->assertSee('data-file-drop', false)->assertSee('name="manuscript"', false);

        $this->get('/profile/public')->assertOk()
            ->assertSee('data-kind="image"', false)->assertSee('name="photo"', false)->assertSee('data-max-mb="2"', false);
    }
}
