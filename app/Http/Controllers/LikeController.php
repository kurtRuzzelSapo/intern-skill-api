<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Http\Requests\StoreLikeRequest;
use App\Http\Requests\UpdateLikeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
          // Validate the incoming request
          $validated = Validator::make($request->all(), [
            'forum_id' => 'required|integer',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(), 403);
        }

        try {
            // Check if the user has already liked this post
            $userLikedPostBefore = Like::where('user_id', Auth::id())
                ->where('forum_id', $request->forum_id)
                ->first();

            if ($userLikedPostBefore) {
                return response()->json(['message' => 'You cannot like a post twice'], 403);
            } else {
                // Create a new like entry
                $like = new Like();
                $like->forum_id = $request->forum_id;
                $like->user_id = Auth::id();
                $like->save();

                // Return success message
                return response()->json([
                        'message' => 'Post liked successfully',
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
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
