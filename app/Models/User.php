<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Archilex\AdvancedTables\Concerns\HasViews;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use HasViews;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'username',
        'orcid_id',
        'affiliation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get all of the reports for the user.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function curatedReports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_user', 'user_id', 'report_id')
            ->using(ReportUser::class)
            ->withPivot('curator_number', 'status', 'comment')
            ->withTimestamps();
    }

    /**
     * Define the relationship with linked social accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function linkedSocialAccounts()
    {
        return $this->hasMany(LinkedSocialAccount::class);
    }

    /**
     * Get the user's full name attribute.
     * This ensures Filament always gets a proper name even if the name field is null.
     */
    public function getNameAttribute(): string
    {
        // If name is already set, use it; otherwise, combine first_name and last_name
        if (! empty($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'Unknown User';
    }

    /**
     * Check if user can access a particular panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'control-panel') {
            return $this->roles()->exists();
        }

        return true;
    }
}
