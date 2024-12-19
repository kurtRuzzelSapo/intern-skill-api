<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Http\Requests\StoreForumRequest;
use App\Http\Requests\UpdateForumRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ResourcesSinglePostResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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


    public function getMyForum()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Retrieve the forums created by the authenticated user
            $forums = Forum::where('user_id', $user->id)->with('comments', 'likes')->get();

            return response()->json([
                'user' => $user,
                'forums' => $forums,
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching user forums: ', [
                'message' => $e->getMessage(),
                'user_id' => $user->id, // Log the user ID passed
            ]);

            return response()->json([
                'error' => 'An error occurred while retrieving the forums.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = FacadesValidator::make($request->all(), [
            'title' => 'required|string',
            'desc' => 'required|string',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(), 403);
        }

        try {
            $post = new Forum();
            $post->title = $request->title;
            $post->desc = $request->desc;

            // Handle image upload
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('forum', 'public');
                $post->image = $path;
            } else {
                $post->image = null; // Explicitly set to null if no image is uploaded
            }

            $post->user_id = Auth::id();
            $post->save();

            return response()->json([
                'message' => 'Post added successfully',
                'post' => $post
            ], 200);

        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()], 403);
        }
    }


// SHARE FEATURE
//     public function shareToProfile(Request $request, Forum $forum)
// {
//     try {
//         $user = Auth::user();

//         // Check if the forum is already shared by the user
//         if ($user->sharedForums()->where('forum_id', $forum->id)->exists()) {
//             return response()->json(['message' => 'Forum already shared to your profile'], 403);
//         }

//         // Attach the forum to the user's shared forums
//         $user->sharedForums()->attach($forum->id);

//         return response()->json([
//             'message' => 'Forum shared to your profile successfully',
//             'shared_forum' => $forum
//         ], 200);

//     } catch (\Exception $th) {
//         return response()->json(['error' => $th->getMessage()], 403);
//     }
// }

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
