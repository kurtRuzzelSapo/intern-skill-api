<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class specialization extends Model
{

    protected $fillable = [
        'specialization',
        'user_id'
    ];

    function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
