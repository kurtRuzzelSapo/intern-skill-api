<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'forum_id',
        'image_path',
    ];

    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }
}
