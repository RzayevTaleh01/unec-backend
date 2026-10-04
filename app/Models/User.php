<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, ResolvesMediaUrl;

    /** Roles a user may switch on or off for themselves (like OJS self-registration: reader, author, reviewer). */
    public const SELF_ROLES = ['reader', 'author', 'reviewer'];

    /** Editorial roles only an administrator can grant. A reviewer still has to be assigned to each article by an editor. */
    public const STAFF_ROLES = ['editor_in_chief', 'section_editor', 'copyeditor', 'typesetter'];

    public const ROLES = ['reader', 'author', 'editor_in_chief', 'section_editor', 'reviewer', 'copyeditor', 'typesetter'];

    /** Roles that may work on the editorial side of the admin panel. */
    public const EDITOR_ROLES = ['editor_in_chief', 'section_editor'];

    protected $fillable = [
        'name', 'first_name', 'last_name', 'publish_name', 'username', 'email', 'password',
        'phone', 'institution', 'country', 'signature', 'bio', 'photo', 'homepage_url',
        'orcid', 'specialty', 'roles', 'working_languages',
        'consent_news', 'consent_reviewer_contact',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'working_languages' => 'array',
            'is_admin' => 'boolean',
            'consent_news' => 'boolean',
            'consent_reviewer_contact' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->first_name || $user->last_name) {
                $user->name = trim($user->first_name.' '.$user->last_name);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin || $this->isEditor();
    }

    public function isEditor(): bool
    {
        return count(array_intersect($this->roles ?? [], self::EDITOR_ROLES)) > 0;
    }

    /** Roles granted by an administrator (never editable by the user). */
    public function staffRoles(): array
    {
        return array_values(array_intersect($this->roles ?? [], self::STAFF_ROLES));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->photo);
    }

    public function journals(): BelongsToMany
    {
        return $this->belongsToMany(Journal::class)->withPivot('roles');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Article::class, 'submitter_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }
}
