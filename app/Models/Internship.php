<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    protected $fillable
    = [ 'recruiter_id', // Foreign key to recruiter_profiles
    'title',
    'desc',
    'skills_required',
    'location',
    'salary',
    'duration',
    'start_status',
    'apply_by',
    'other_requirements',
];

   public function recruiter()
   {
       return $this->belongsTo(RecruiterProfile::class);
   }


   public function applications()
{
    return $this->hasMany(Application::class);
}

public function applicants()
{
    return $this->belongsToMany(User::class, 'applications', 'internship_id', 'applicant_id')
                ->withPivot('cover_letter', 'resume', 'status', 'created_at', 'updated_at');
}
}
