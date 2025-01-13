<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {


            $comments = Comment::with('user')->get();
            return response()->json($comments,200);
        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()],403);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(),[
            'forum_id' => 'required|integer',
            'comment' => 'required|string',
            'user_id' => 'required|string',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(),403);
        }

        try {
            $comment = new Comment();
            $comment->forum_id = $request->forum_id;
            $comment->comment = $request->comment;
            $comment->user_id = $request->user_id;
            $comment->save();

             //return
             return response()->json([
                'message' => 'Comment added successfully',
            ],200);

        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()],403);
        }
    }

    public function reply(Request $request)
    {
        $request->validate([
            'forum_id' => 'required|exists:forums,id',
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id', // Ensure parent_id references a valid comment
        ]);

        $comment = Comment::create([
            'user_id' => $request->user_id,
            'forum_id' => $request->forum_id,
            'parent_id' => $request->parent_id, // NULL for top-level comments
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Comment added successfully!', 'comment' => $comment], 201);
    }

    /**
     * Display the specified resource.
     */
    // public function showComments($forumId)
    // {
    //     try {
    //         $comments = Comment::where('forum_id', $forumId)
    //             ->whereNull('parent_id')
    //             ->with(['replies.user', 'user'])
    //             ->get();

    //         // Debugging: Log the query result
    //         logger($comments);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Comments fetched successfully.',
    //             'data' => $comments,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while fetching comments.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function showComments($forumId)
{
    try {
        $comments = Comment::where('forum_id', $forumId)
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->with('user');
            }, 'user'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Comments fetched successfully.',
            'data' => $comments,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while fetching comments.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the internship record
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json(['error' => 'Comment not found'], 404);
            }

            // Validate incoming request
            $validatedData = $request->validate([
                'comment' => 'required|string',
            ]);

            // Update the internship record with validated data
            $comment->update([
                'comment' => $request->comment ?? $comment->comment,
            ]);

            return response()->json($comment, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error.',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Comment Update Error: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while updating the comment.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Find the intern by ID
            $comment = Comment::find($id);

            // Check if the intern exists
            if (!$comment) {
                return response()->json(['error' => 'Comment not found'], 404);
            }

            // Delete the intern profile
            $comment->delete();

            return response()->json(['message' => 'Comment deleted successfully'], 200);

        } catch (\Throwable $e) {
            // Log the error for debugging
            Log::error('Comment Delete Error', ['id' => $id, 'error' => $e->getMessage()]);

            // Return a generic error response
            return response()->json([
                'error' => 'An error occurred while deleting the comment.',
            ], 500);
        }
    }
}
