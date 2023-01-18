<?php

namespace App\Models;

class UserRole extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'role_id',
        'status',
        'answered_by',
        'answered_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'role_name',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name;
    }
}
