<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternProfile extends Model
{
    protected $fillable
    = ['user_id', 'school', 'degree', 'about', 'cover_image'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'intern_skills', 'intern_id', 'skill_id');
    }
}
