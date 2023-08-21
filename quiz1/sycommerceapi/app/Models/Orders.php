<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;
    protected $fillable = [
        'userId',
        "productIds",
        "orderstatus",
        'paymentstatus',
        'totalamount'
    ];

    // Define the one-to-one relationship with user
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userId');
    }
}
