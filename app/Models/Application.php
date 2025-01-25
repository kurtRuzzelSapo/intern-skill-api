<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = ['internship_id', 'applicant_id', 'cover_letter', 'resume', 'status'];


    public function users()
{
    return $this->belongsTo(User::class, 'user_id');
}




    public function internship()
{
    return $this->belongsTo(Internship::class);
}

public function applicant()
{
    return $this->belongsTo(User::class, 'applicant_id'); // Assuming applicant_id is a foreign key to the users table
}
}
