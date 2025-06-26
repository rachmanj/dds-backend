<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'nik',
        'project',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->department ? $this->department->location_code : null;
    }

    // Distribution relationships
    public function createdDistributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'created_by');
    }

    public function senderVerifiedDistributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'sender_verified_by');
    }

    public function receiverVerifiedDistributions(): HasMany
    {
        return $this->hasMany(Distribution::class, 'receiver_verified_by');
    }

    public function distributionHistories(): HasMany
    {
        return $this->hasMany(DistributionHistory::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreferences::class);
    }

    public function getPreferencesAttribute()
    {
        if (!$this->preferences) {
            return UserPreferences::create(['user_id' => $this->id]);
        }
        return $this->preferences;
    }
}
