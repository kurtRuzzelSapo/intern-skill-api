<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Http\Requests\StoreForumRequest;
use App\Http\Requests\UpdateForumRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ResourcesSinglePostResource;
class ForumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $posts = Forum::with('user', 'comments', 'likes')
                ->withCount('comments', 'likes')
                ->latest()
                ->get();

            return response()->json([
                'posts' => $posts
            ], 200);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 403);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = FacadesValidator::make($request->all(),[
            'title' => 'required|string',
            'desc' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(),403);
        }

        try {
            $post = new Forum();
            $post->title = $request->title;
            $post->desc = $request->desc;
            $post->image = $request->image;
            $post->user_id = Auth::id();

            $post->save();

             //return
             return response()->json([
                'message' => 'Post added successfully',
            ],200);

        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()],403);
        }
    }

    /**
     * Display the specified resource.
     */
     //show a single post
    public function show(Forum $forum)
    {
        try {
            return new ResourcesSinglePostResource($forum);
        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()], 403);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateForumRequest $request, Forum $forum)
    {
        // Validate the incoming data
        $validated = $request->validate([
            'title' => 'required|string',
            'desc' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            // Update the recipe model directly
            $forum->update([
                'title' => $request->title,
                'desc' => $request->desc,
                'image' => $request->image,
            ]);

            // Return the updated recipe
            return response()->json([
                'message' => 'Post updated successfully',
                'updated_post' => $forum, // Return the updated recipe instance
            ], 200);

        } catch (\Exception $th) {
            // Return a 500 internal server error for unexpected issues
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Forum $forum)
    {
        try {
            $forum->delete();
            return response()->json([
                'message' => 'Post deleted successfully',
            ], 200);
        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()], 403);
        }
    }

    public function restore(Forum $forum)
{
    try {
        // Restore the soft-deleted recipe
        $forum->restore();

        return response()->json([
            'message' => 'Post restored successfully',
            'post' => $forum
        ], 200);

    } catch (\Exception $th) {
        return response()->json([
            'error' => 'Something went wrong: ' . $th->getMessage()
        ], 500);
    }
}


// Completey deleted in the database
public function forceDestroy(Forum $forum)
 {
        try {
            // Permanently delete the recipe (bypasses soft delete)
            $forum->forceDelete();

            return response()->json([
                'message' => 'Post permanently deleted',
                'post' => $forum,
            ], 200);

        } catch (\Exception $th) {
            return response()->json([
                'error' => 'Something went wrong: ' . $th->getMessage()
            ], 500);
        }
  }
}
