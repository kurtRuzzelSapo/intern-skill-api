<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipSkill extends Model
{
    use HasFactory;

    // Specify the table name
    protected $table = 'internship_skill';

    // Define the fillable fields
    protected $fillable = ['internship_id', 'skill'];
}
