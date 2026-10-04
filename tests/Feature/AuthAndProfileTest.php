<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'first_name' => 'Kamran', 'last_name' => 'Talibli', 'username' => 'kamran', 'roles' => ['reader'],
        ], $attributes));
    }

    public function test_two_step_registration_creates_user(): void
    {
        $this->get('/register/account')->assertRedirect('/register/personal');

        $this->post('/register/personal', [
            'first_name' => 'Ayşə', 'last_name' => 'Məmmədova', 'institution' => 'UNEC', 'country' => 'az',
        ])->assertRedirect('/register/account');

        $this->post('/register/account', [
            'email' => 'ayse@example.com', 'username' => 'ayse_m', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'consent_privacy' => '1', 'consent_reviewer_contact' => '1',
        ])->assertRedirect('/register/success');

        $user = User::where('email', 'ayse@example.com')->firstOrFail();
        $this->assertSame('Ayşə Məmmədova', $user->name);
        // Interest in reviewing is only a flag; the reviewer role is granted by an administrator.
        $this->assertSame(['reader'], $user->roles);
        $this->assertTrue($user->consent_reviewer_contact);
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('Secret123', $user->password));
    }

    public function test_registration_validation(): void
    {
        $this->user(['email' => 'taken@example.com', 'username' => 'taken']);
        $this->post('/register/personal', ['first_name' => 'A', 'last_name' => 'B', 'institution' => 'C', 'country' => 'az']);

        $this->post('/register/account', [
            'email' => 'taken@example.com', 'username' => 'taken', 'password' => 'short', 'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['email', 'username', 'password', 'consent_privacy']);
    }

    public function test_cannot_mass_assign_admin_flag_through_registration(): void
    {
        $this->post('/register/personal', ['first_name' => 'A', 'last_name' => 'B', 'institution' => 'C', 'country' => 'az']);
        $this->post('/register/account', [
            'email' => 'sneaky@example.com', 'username' => 'sneaky', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'consent_privacy' => '1', 'is_admin' => '1',
        ]);

        $this->assertFalse(User::where('email', 'sneaky@example.com')->firstOrFail()->is_admin);
    }

    public function test_login_by_email_or_username_and_logout(): void
    {
        $user = $this->user(['email' => 'k@example.com', 'password' => 'Secret123']);

        $this->post('/login', ['login' => 'k@example.com', 'password' => 'Secret123'])->assertRedirect('/profile/identity');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/login', ['login' => 'kamran', 'password' => 'Secret123'])->assertRedirect('/profile/identity');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_failure_is_throttled(): void
    {
        $this->user(['email' => 'k@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'k@example.com', 'password' => 'wrong'])->assertSessionHasErrors('login');
        }

        // Even the right password is refused once the limit is hit.
        $this->post('/login', ['login' => 'k@example.com', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_password_reset_does_not_reveal_whether_account_exists(): void
    {
        Notification::fake();
        $user = $this->user(['email' => 'k@example.com']);

        $this->post('/forgot-password', ['email' => 'k@example.com'])->assertRedirect('/forgot-password/sent');
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect('/forgot-password/sent');

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_password_reset_flow(): void
    {
        $user = $this->user(['email' => 'k@example.com']);
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $this->get("/reset-password/{$token}?email=k@example.com")->assertOk();

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'k@example.com', 'password' => 'Brandnew123', 'password_confirmation' => 'Brandnew123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('Brandnew123', $user->fresh()->password));
    }

    public function test_every_profile_tab_is_a_separate_page_and_requires_login(): void
    {
        $paths = ['identity', 'contact', 'roles', 'public', 'password', 'notifications', 'api-key', 'submissions'];

        foreach ($paths as $path) {
            $this->get("/profile/{$path}")->assertRedirect('/login');
        }

        $this->seed(ContentSeeder::class);
        $this->actingAs($this->user());

        foreach ($paths as $path) {
            $this->get("/profile/{$path}")->assertOk();
        }
    }

    public function test_identity_and_contact_updates(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put('/profile/identity', ['first_name' => 'Kamal', 'last_name' => 'Ali', 'publish_name' => 'Dr. K. Ali'])->assertSessionHasNoErrors();
        $this->assertSame('Kamal Ali', $user->fresh()->name);

        $this->put('/profile/contact', [
            'email' => 'new@example.com', 'phone' => '+994501234567', 'institution' => 'UNEC', 'country' => 'tr',
            'signature' => '<b>Sig</b><script>x</script>', 'working_languages' => ['az', 'en'],
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame(['az', 'en'], $user->working_languages);
        $this->assertStringNotContainsString('<script>', $user->signature);
    }

    public function test_contact_email_must_stay_unique(): void
    {
        $this->user(['email' => 'taken@example.com', 'username' => 'other']);
        $this->actingAs($this->user(['email' => 'mine@example.com']));

        $this->put('/profile/contact', ['email' => 'taken@example.com', 'country' => 'az'])->assertSessionHasErrors('email');
    }

    public function test_roles_and_other_journals(): void
    {
        $this->seed(ContentSeeder::class);
        $user = $this->user();
        $this->actingAs($user);
        $journal = Journal::where('is_primary', false)->first();

        $this->put('/profile/roles', ['roles' => ['reader', 'author'], 'specialty' => 'Finance'])->assertSessionHasNoErrors();
        $this->assertSame(['reader', 'author'], $user->fresh()->roles);

        // Nobody can promote themselves: staff roles and made-up roles are rejected.
        foreach (['admin', 'reviewer', 'editor_in_chief', 'section_editor', 'copyeditor', 'typesetter'] as $forbidden) {
            $this->put('/profile/roles', ['roles' => ['reader', $forbidden]])->assertSessionHasErrors('roles.1');
        }
        $this->assertSame(['reader', 'author'], $user->fresh()->roles);
        $this->put('/profile/roles', ['roles' => []])->assertSessionHasErrors('roles');

        $this->put('/profile/roles/journals', ['journals' => [$journal->id => ['reader', 'author']]])->assertSessionHasNoErrors();
        $this->assertSame(['reader', 'author'], json_decode($user->journals()->first()->pivot->roles, true));
        $this->put('/profile/roles/journals', ['journals' => [$journal->id => ['reviewer']]])->assertSessionHasErrors('journals.'.$journal->id.'.0');

        $this->put('/profile/roles/journals', ['journals' => []]);
        $this->assertCount(0, $user->journals()->get());
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->user(['password' => 'Oldpass123']);
        $this->actingAs($user);

        $this->put('/profile/password', ['current_password' => 'nope', 'password' => 'Newpass123', 'password_confirmation' => 'Newpass123'])
            ->assertSessionHasErrors('current_password');

        $this->put('/profile/password', ['current_password' => 'Oldpass123', 'password' => 'Newpass123', 'password_confirmation' => 'Newpass123'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Newpass123', $user->fresh()->password));
    }

    public function test_notification_preferences_are_saved(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put('/profile/notifications', [
            'in_app' => ['announcement_created' => '1'],
            'email_off' => ['announcement_created' => '1', 'weekly_digest' => '1'],
        ])->assertSessionHasNoErrors();

        $settings = $user->notificationSettings()->get()->keyBy('type');
        $this->assertTrue($settings['announcement_created']->in_app);
        $this->assertFalse($settings['announcement_created']->email);
        $this->assertFalse($settings['issue_published']->in_app);
        $this->assertTrue($settings['issue_published']->email);
        $this->assertFalse($settings['weekly_digest']->email);
    }

    public function test_profile_photo_upload_validates_type(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $this->actingAs($user);

        $this->put('/profile/public', ['photo' => UploadedFile::fake()->create('evil.php', 10)])->assertSessionHasErrors('photo');

        $this->put('/profile/public', ['photo' => UploadedFile::fake()->image('me.jpg'), 'orcid' => '0000-0002-1825-0097', 'homepage_url' => 'https://example.com'])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists($user->fresh()->photo);
        $this->put('/profile/public', ['orcid' => 'bad'])->assertSessionHasErrors('orcid');
    }

    public function test_api_key_grants_api_access(): void
    {
        $this->seed(ContentSeeder::class);
        $user = $this->user();
        $this->actingAs($user);

        $this->getJson('/api/v1/me')->assertOk(); // session guard is irrelevant to sanctum here, covered below

        $response = $this->post('/profile/api-key');
        $token = session('api_token');
        $this->assertNotEmpty($token);

        auth()->guard('web')->logout();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/articles')->assertOk()->assertJsonStructure(['data' => [['id', 'title', 'authors']]]);

        $this->delete('/profile/api-key');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_api_requires_token(): void
    {
        $this->getJson('/api/v1/articles')->assertUnauthorized();
    }

    public function test_only_admins_can_open_admin_panel(): void
    {
        $this->actingAs($this->user())->get('/admin')->assertForbidden();

        $admin = $this->user(['email' => 'admin@example.com', 'username' => 'adm']);
        $admin->is_admin = true;
        $admin->save();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_other_journals_open_as_a_modal_on_the_roles_page(): void
    {
        $this->seed(ContentSeeder::class);
        $user = $this->user();
        $this->actingAs($user);
        $journal = Journal::where('is_primary', false)->first();

        $html = $this->get('/profile/roles')->assertOk()->getContent();

        // A dialog with its own form, listing every non-primary journal with the self-service roles only.
        $this->assertStringContainsString('data-role-modal', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('data-role-modal-open', $html);
        $this->assertStringContainsString('action="'.route('profile.roles.journals.update').'"', $html);
        $this->assertStringContainsString($journal->name, html_entity_decode($html));
        $this->assertStringContainsString('name="journals['.$journal->id.'][]" value="reader"', $html);
        $this->assertStringNotContainsString('value="reviewer"', $html);
        $this->assertStringNotContainsString(' data-open', $html);
        $this->assertStringContainsString('hidden', $html);

        // The former standalone page now lands on the roles page and opens the dialog.
        $this->get('/profile/roles/journals')->assertRedirect('/profile/roles#journals');

        // Invalid input re-renders the page with the dialog open.
        $this->from('/profile/roles')->put('/profile/roles/journals', ['journals' => [$journal->id => ['reviewer']]])
            ->assertRedirect('/profile/roles');
        $this->get('/profile/roles')->assertSee('data-open', false);
    }
}
