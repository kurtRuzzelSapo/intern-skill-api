<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternSkill extends Model
{
    protected $fillable
    = ['intern_id', 'skill_id'];


    public function user()
    {
        return $this->belongsToMany(InternProfile::class, 'intern_id');
    }
}
