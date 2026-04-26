<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{

    use  HasRoles, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'username',
        'first_name',
        'last_name',
        'job_position',
        'deletion_reason',
        'email',
        'phone',
        'password',
        'profile_photo',
        'role',
        'status',
        'password_otp',
        'password_otp_expired_at',
        'password_otp_verified_at',
        'password_reset_token',
        'password_reset_token_expires_at',
        'email_verified_at',
        'terms_and_conditions',
        'address',
        'zipcode',
        'last_login_at',
        'user_type'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
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

    public function getAvatarAttribute(): ?string
    {
        return $this->profile_photo;
    }

    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }


    // implement 2 methods for token get
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
