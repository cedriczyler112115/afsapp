<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'level_id',
        'division_id',
        'section_id',
        'province',
        'cluster',
        'group_id',
        'municipality',
        'is_status',
        'google_id',
        'google_name',
        'google_email',
        'google_avatar',
        'access_rights',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'access_rights' => 'array',
        ];
    }

    public function getLevelIdAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['u_level'] ?? null;
    }

    public function setLevelIdAttribute($value)
    {
        $this->attributes['level_id'] = $value;
    }

    public function getULevelAttribute()
    {
        return $this->level_id;
    }

    public function setULevelAttribute($value)
    {
        $this->attributes['level_id'] = $value;
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'borrower_id');
    }

    public function issuedBorrowings()
    {
        return $this->hasMany(Borrowing::class, 'issued_by');
    }

    public function userLevel()
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'id');
    }

    public function hasSidebarAccess(string $moduleKey): bool
    {
        $userLevelAccess = [];
        if ($this->userLevel && !empty($this->userLevel->access_rights)) {
            $userLevelAccess = $this->userLevel->access_rights;
        }

        $userSpecificAccess = [];
        if (!empty($this->access_rights)) {
            $userSpecificAccess = $this->access_rights;
        }

        return in_array($moduleKey, $userLevelAccess, true) || in_array($moduleKey, $userSpecificAccess, true);
    }
}
