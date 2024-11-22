<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    protected $fillable
    = [ 'recruiter_id', // Foreign key to recruiter_profiles
    'title',
    'desc',
    'requirements',
    'cover_post', // Image path for cover post
    'location',
    'salary',
    'duration',];

   public function recruiter()
   {
       return $this->belongsTo(InternProfile::class);
   }
}
