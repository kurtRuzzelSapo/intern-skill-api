<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourcesSinglePostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' =>$this->title,
            'desc' =>$this->desc,
            'image' =>$this->image,
            'published_at' => $this->created_at,
            'last_update' => $this->updated_at,
            'Author' => User::findorFail($this->user_id),
            'comment_count' => Comment::where('post_id',$this->id)->count(),
            'likes_count' => Like::where('post_id',$this->id)
            ->where('user_id',$this->user_id)->count(),
            'comments' => Comment::where('post_id',$this->id)->get(),

        ];
    }
}
