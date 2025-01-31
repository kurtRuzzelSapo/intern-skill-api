<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Forum;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullname',
        'email',
        'password',
        'phone_number',
        'gender',
        'address',
        'profile_image',
        'role'
    ];


    public function applications()
{
    return $this->hasMany(Application::class, 'applicant_id');
}

public function appliedInternships()
{
    return $this->belongsToMany(Internship::class, 'applications', 'applicant_id', 'internship_id')
                ->withPivot('cover_letter', 'resume', 'status', 'created_at', 'updated_at');
}


public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}




    public function internProfile()
    {
        return $this->hasOne(InternProfile::class, 'user_id');
    }

    public function recruiterProfile()
    {
        return $this->hasOne(RecruiterProfile::class);
    }

    public function recruiters()
    {
        return $this->hasMany(RecruiterProfile::class);
    }

    public function sharedForums()
    {
        return $this->belongsToMany(Forum::class, 'forum_user')->withTimestamps();
    }

    public function specializations()
    {
    return $this->hasMany(Specialization::class);
    }



    public function skills()
    {
    return $this->hasMany(Skill::class); // A user can have many skills
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
