<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deletedaccounts extends Model
{
    use HasFactory;
    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'type',
        'wallet',
        'activatedstatus',
        'profileurl',
        'deletiondate',
    ];
}
