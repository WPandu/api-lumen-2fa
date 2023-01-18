<?php

namespace App\Models;

class Role extends Model
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_CONTRIBUTOR = 'contributor';

    protected $fillable = [
        'name',
        'description',
    ];
}
