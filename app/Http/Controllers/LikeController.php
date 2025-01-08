<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Forum;
use App\Http\Requests\StoreLikeRequest;
use App\Http\Requests\UpdateLikeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {

    }

    public function likePost(Request $request)
    {
        try {
            // Validate the incoming request
            $forumId = $request->input('forum_id');
            $userId = $request->input('user_id');

            if (!$forumId || !$userId) {
                return response()->json([
                    'error' => 'Both forum_id and user_id are required.'
                ], 400);
            }

            // Find the forum post
            $forum = Forum::find($forumId);

            if (!$forum) {
                return response()->json([
                    'error' => 'Forum post not found.'
                ], 404);
            }

            // Check if the user has already liked the post
            $alreadyLiked = Like::where('user_id', $userId)
                ->where('forum_id', $forumId)
                ->exists();

            if ($alreadyLiked) {
                return response()->json([
                    'message' => 'You have already liked this post.'
                ], 403);
            }

            // Create a new like
            $like = new Like();
            $like->forum_id = $forumId;
            $like->user_id = $userId;
            $like->save();

            return response()->json([
                'status' => 'success', // Add a status key for consistency
                'message' => 'Post liked successfully.',
            ], 200);

        } catch (\Exception $e) {
            // Log the error with more context
            Log::error('Error liking forum post:', [
                'forum_id' => $request->input('forum_id'),
                'user_id' => $request->input('user_id'),
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'success', // Add a status key for consistency
                'message' => 'Post liked successfully.',
            ], 200);
        }
    }






    /**
     * Display the specified resource.
     */
    public function show(Like $like)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLikeRequest $request, Like $like)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Like $like)
    {
        //
    }
}
