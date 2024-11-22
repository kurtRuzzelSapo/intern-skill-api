<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable
    = ['skill_name'];

    public function interns()
    {
        return $this->belongsToMany(InternProfile::class, 'intern_skills', 'skill_id', 'intern_id');
    }
    public function internSkill()
    {
        return $this->belongsToMany(InternSkill::class,  'skill_id');
    }
}
