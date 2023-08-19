<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activitylogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'userid',
        'action',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
