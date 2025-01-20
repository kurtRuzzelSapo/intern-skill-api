<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public function user()
{
    return $this->belongsTo(User::class); // Each skill belongs to one user
}
}
