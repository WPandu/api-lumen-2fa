<?php

namespace App\Models;

use Auth;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Lumen\Auth\Authorizable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable;
    use Authorizable;

    public const FOLDER_NAME = 'user/photo';

    protected $fillable = [
        'email',
        'password',
        'name',
        'phone',
        'is_active',
        'google2fa_secret',
        'activation_token',
        'reset_token',
        'created_by',
        'updated_by',
        'active_at',
        'photo',
        'last_token',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'active_at',
        'deactivate_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Hash member's password here
     * Update member hashed password to use stronger hash algorithm
     *
     * @param $pass
     */
    public function setPasswordAttribute($pass)
    {
        $this->attributes['password'] = app('hash')->make($pass);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function getTokenAttribute()
    {
        if (Auth::guest()) {
            return null;
        }

        return Auth::getToken();
    }

    public function getIsAdminAttribute()
    {
        return $this->roles()->where('role_id', config('role.admin'))->exists();
    }

    public function getIsContributorAttribute()
    {
        return $this->roles()->where('role_id', config('role.contributor'))->where(
            'status',
            UserRole::STATUS_APPROVED
        )->exists();
    }

    public function getIsCustomerAttribute()
    {
        return $this->roles()->where('role_id', config('role.customer'))->exists();
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return get_file_url(self::FOLDER_NAME, $this->photo);
        }

        $name = str_replace(' ', '+', $this->name);

        $length = count(explode('+', $name));

        if ($length >= 3) {
            $length = 3;
        }

        return sprintf('%s/api/?name=%s&rounded=true&length=%s', env('UI_AVATAR_URL'), $name, $length);
    }
}
