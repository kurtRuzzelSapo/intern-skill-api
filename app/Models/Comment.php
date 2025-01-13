<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comment',
        'forum_id',
        'parent_id'
    ];

        // Relationship to parent comment
        public function parent()
        {
            return $this->belongsTo(Comment::class, 'parent_id');
        }

        // Relationship to child comments (replies)
        public function replies()
        {
            return $this->hasMany(Comment::class, 'parent_id')->with('replies'); // Recursive relationship
        }


        function user()
        {
        return $this->belongsTo(User::class,'user_id');
        }

        public function forum()
        {
            return $this->belongsTo(Forum::class);
        }
}
