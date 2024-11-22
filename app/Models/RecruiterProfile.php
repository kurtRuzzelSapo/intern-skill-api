<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruiterProfile extends Model
{
    protected $fillable
     = ['user_id', 'company', 'industry', 'position'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
