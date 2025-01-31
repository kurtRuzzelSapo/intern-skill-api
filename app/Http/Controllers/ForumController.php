<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Http\Requests\StoreForumRequest;
use App\Http\Requests\UpdateForumRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ResourcesSinglePostResource;
use App\Models\specialization;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ForumController extends Controller
{
     public function index()
     {
         try {
             // Get all posts
             $posts = Forum::with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($post) {
                     // Map over the images to include the full URL
                     $post->images = $post->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path); // Add the full URL to the image path
                         return $image;
                     });
                     return $post;
                 });

             // Get top 3 posts
             $topPosts = Forum::with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->orderByRaw('(likes_count + comments_count) DESC')
                 ->limit(3)
                 ->get()
                 ->map(function ($post) {
                     // Map over the images to include the full URL
                     $post->images = $post->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $post;
                 });

             // Get posts by interns
             $internPosts = Forum::whereHas('user', function ($query) {
                 $query->where('role', 'intern');
             })->with('user', 'comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($post) {
                     // Map over the images to include the full URL
                     $post->images = $post->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $post;
                 });

             // Get posts by recruiters
             $recruiterPosts = Forum::whereHas('user', function ($query) {
                 $query->where('role', 'recruiter');
             })->with('user', 'comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($post) {
                     // Map over the images to include the full URL
                     $post->images = $post->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $post;
                 });

             // Return all posts with categories
             return response()->json([
                 'posts' => $posts,
                 'top_posts' => $topPosts,
                 'intern_posts' => $internPosts,
                 'recruiter_posts' => $recruiterPosts
             ], 200);

         } catch (\Exception $exception) {
             return response()->json(['error' => $exception->getMessage()], 403);
         }
     }


     public function getFilteredForumsByUserSpecializations(Request $request)
     {
        
         try {
             // Validate that user_id is provided
             $userId = $request->user_id;
             if (!$userId) {
                 return response()->json(['error' => 'User ID is required'], 400);
             }

             // Fetch the user's specializations from the Specialization table
             $specializations = Specialization::where('user_id', $userId)->pluck('specialization');
             if ($specializations->isEmpty()) {
                 return response()->json(['error' => 'No specializations found for the user'], 404);
             }

             // Get all forums filtered by user's specializations
             $forums = Forum::whereIn('specialization', $specializations)
                 ->with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($forum) {
                     $forum->images = $forum->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $forum;
                 });

             // Get top 3 forums filtered by user's specializations
             $topForums = Forum::whereIn('specialization', $specializations)
                 ->with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->orderByRaw('(likes_count + comments_count) DESC')
                 ->limit(3)
                 ->get()
                 ->map(function ($forum) {
                     $forum->images = $forum->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $forum;
                 });

             // Get intern forums filtered by user's specializations
             $internForums = Forum::whereIn('specialization', $specializations)
                 ->whereHas('user', function ($query) {
                     $query->where('role', 'intern');
                 })
                 ->with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($forum) {
                     $forum->images = $forum->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $forum;
                 });

             // Get recruiter forums filtered by user's specializations
             $recruiterForums = Forum::whereIn('specialization', $specializations)
                 ->whereHas('user', function ($query) {
                     $query->where('role', 'recruiter');
                 })
                 ->with('user', 'comments', 'likes', 'images')
                 ->withCount('comments', 'likes', 'images')
                 ->latest()
                 ->get()
                 ->map(function ($forum) {
                     $forum->images = $forum->images->map(function ($image) {
                         $image->image_path = Storage::url($image->image_path);
                         return $image;
                     });
                     return $forum;
                 });

             // Return all filtered posts
             return response()->json([
                 'posts' => $forums,
                 'top_posts' => $topForums,
                 'intern_posts' => $internForums,
                 'recruiter_posts' => $recruiterForums
             ], 200);
         } catch (\Exception $exception) {
             return response()->json(['error' => $exception->getMessage()], 500);
         }

     }








//     public function index()
// {
//     try {
//         $posts = Forum::with('user', 'comments', 'likes')
//             ->withCount('comments', 'likes')
//             ->latest()
//             ->get();

//         // Add the full URL to the image field for each post
//         $posts->each(function ($post) {
//             $post->image = $post->image ? url('storage/' . $post->image) : null;
//         });

//         return response()->json([
//             'posts' => $posts
//         ], 200);
//     } catch (\Exception $exception) {
//         return response()->json(['error' => $exception->getMessage()], 403);
//     }
// }



    public function getMyForum($userId)
    {
        try {
            // Get the authenticated user
            $user = User::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Retrieve the forums created by the authenticated user
            $forums = Forum::where('user_id', $user->id)
            ->with('comments', 'likes')
            ->withCount('comments', 'likes')
            ->latest()
            ->get();

            $forums->each(function ($forums) {
                $forums->image = $forums->image ? url('storage/' . $forums->image) : null;
            });

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
            'specialization' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate multiple images
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(), 403);
        }

        try {
            $post = new Forum();
            $post->title = $request->title;
            $post->desc = $request->desc;
            $post->specialization = $request->specialization;

            // Save the forum post first to get its ID
            $post->user_id = $request->user_id;
            $post->save();

            // Handle image upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('forum_images', 'public'); // Store image
                    // Save image path in forum_images table
                    $post->images()->create(['image_path' => $path, 'forum_id' => $post->id]);
                }
            }

            // Prepare response with full image URLs
            $post->images = $post->images()->get()->map(function ($image) {
                return url('storage/' . $image->image_path);
            });

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




// public function getFilterForums(Request $request)
// {
//     try {
//         // Get the authenticated user from the request
//         $user = $request->user();
//         if (!$user) {
//             return response()->json(['error' => 'User not authenticated'], 401);
//         }

//         // Get the user's specializations
//         $specializations = $user->specializations->pluck('specialization');

//         // Get forums based on specialization
//         $forumsBySpecialization = Forum::whereHas('user.specializations', function ($query) use ($specializations) {
//             $query->whereIn('specialization', $specializations);
//         })
//             ->with('user', 'comments', 'likes', 'images')
//             ->withCount('comments', 'likes', 'images')
//             ->latest()
//             ->get()
//             ->map(function ($post) {
//                 // Map over the images to include the full URL
//                 $post->images = $post->images->map(function ($image) {
//                     $image->image_path = Storage::url($image->image_path);
//                     return $image;
//                 });
//                 return $post;
//             });

//         // Return the forums filtered by specialization
//         return response()->json([
//             'forums_by_specialization' => $forumsBySpecialization,
//         ], 200);

//     } catch (\Exception $exception) {
//         return response()->json(['error' => $exception->getMessage()], 403);
//     }
// }

}
