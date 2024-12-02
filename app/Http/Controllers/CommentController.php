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

            $comments = Comment::all();
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
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(),403);
        }

        try {
            $comment = new Comment();
            $comment->forum_id = $request->forum_id;
            $comment->comment = $request->comment;
            $comment->user_id = Auth::id();
            $comment->save();

             //return
             return response()->json([
                'message' => 'Comment added successfully',
            ],200);

        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()],403);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
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
