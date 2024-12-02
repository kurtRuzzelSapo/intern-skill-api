<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Forum extends Model
{
    /** @use HasFactory<\Database\Factories\ForumFactory> */
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [
        'title',
        'desc',
        'image'
    ];

    protected $dates = ['deleted_at'];

    function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    function comments(){
        return $this->hasMany(Comment::class,'forum_id');
    }

    function likes(){
        return $this->hasMany(Like::class,'forum_id');
    }
    function allLikes(){
        return $this->hasMany(Like::class,'forum_id');
    }
}