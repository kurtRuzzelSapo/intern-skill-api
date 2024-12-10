<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Internship;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

//This is for creating the Internship for Intern who wants to apply
class InternshipController extends Controller
{
    // Display a list of recruiter profiles
    public function index()
    {
        $interns = Internship::with(['recruiter.user'])->get();
        return response()->json($interns, 200);
    }

    // Create a post for Internship
    public function store(Request $request)
    {
        try {
            // Log the incoming request data
            Log::info('Request data:', $request->all());

            // Validate the request data
            $validated = $request->validate([
                'recruiter_id' => 'required|exists:recruiter_profiles,id',
                'title' => 'required|string',
                'desc' => 'required|string',
                'skills_required' => 'nullable|string',
                'location' => 'required|string',
                'salary' => 'required|numeric',
                'duration' => 'required|string',
                'start_status' => 'required|string',
                'apply_by' => 'required|string',
                'other_requirements' => 'required|string',
            ]);

            // Create the internship using validated data
            $intern = Internship::create([
                'recruiter_id' => $validated['recruiter_id'],
                'title' => $validated['title'],
                'desc' => $validated['desc'],
                'skills_required' => $validated['skills_required'] ?? null,
                'location' => $validated['location'],
                'salary' => $validated['salary'],
                'duration' => $validated['duration'],
                'start_status' => $validated['start_status'],
                'apply_by' => $validated['apply_by'],
                'other_requirements' => $validated['other_requirements'],
            ]);

            return response()->json($intern, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            Log::error('Validation Error:', ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Internship Creation Error:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while creating the Internship.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Show a specific intern profile
    // Text Search based of Title and Location
    public function show(Request $request, $id = null)
{
    try {
        // If an ID is provided, return a specific internship
        if ($id) {
            $internship = Internship::with('recruiter')->find($id);

            if (!$internship) {
                return response()->json(['error' => 'Internship not found'], 404);
            }

            return response()->json($internship, 200);
        }

        // Otherwise, perform a search based on title and location
        $title = $request->input('title');
        $location = $request->input('location');

        $query = Internship::query();

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        if ($location) {
            $query->where('location', 'like', '%' . $location . '%');
        }

        $results = $query->with('recruiter')->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        return response()->json($results, 200);
    } catch (Exception $e) {
        Log::error('Show/Search Error: ', ['message' => $e->getMessage()]);
        return response()->json([
            'error' => 'An error occurred while retrieving the data.',
            'details' => $e->getMessage()
        ], 500);
    }
}

    // Update an existing intern profile
    public function update(Request $request, $id)
    {
        try {
            // Find the internship record
            $intern = Internship::find($id);

            if (!$intern) {
                return response()->json(['error' => 'Internship not found'], 404);
            }

            // Validate incoming request
            $validatedData = $request->validate([
                'recruiter_id' => 'required|exists:recruiter_profiles,id',
                'title' => 'required|string|max:255',
                'desc' => 'required|string',
                'requirements' => 'required|string',
                'cover_post' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
                'location' => 'required|string',
                'salary' => 'required|numeric',
                'duration' => 'required|string',
                'start_status' => 'required|string',
                'apply_by' => 'required|string',
                ' other_requirements' => 'required|string',
            ]);



            // Handle cover_post upload if provided
            $coverPostPath = $intern->cover_post; // Keep the old image path by default

            if ($request->hasFile('cover_post')) {
                // Delete the old cover image if it exists
                if ($coverPostPath && Storage::exists('public/' . $coverPostPath)) {
                    Storage::delete('public/' . $coverPostPath);
                }

                // Store the new cover image and get the path
                $coverImage = $request->file('cover_post');
                $coverPostPath = $coverImage->store('images', 'public');
            }

            // Update the internship record with validated data
            $intern->update([
                'recruiter_id' => $request->recruiter_id ?? $intern->recruiter_id,
                'title' => $request->title ?? $intern->title,
                'desc' => $request->desc ?? $intern->desc,
                'requirements' => $request->requirements ?? $intern->requirements,
                'cover_post' => $coverPostPath ?? $intern->cover_post, // Use the updated cover image path
                'location' => $request->location ?? $intern->location,
                'salary' => $request->salary ?? $intern->salary,
                'duration' => $request->duration ?? $intern->duration,
                'start_status' => $request->start_status ?? $intern->start_status,
                'apply_by' => $request->apply_by ?? $intern->apply_by,
                'other_requirements' => $request->other_requirements ?? $intern->other_requirements,
            ]);

            return response()->json($intern, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error.',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Internship Update Error: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while updating the internship.',
                'details' => $e->getMessage()
            ], 500);
        }
    }






    // Delete an intern profile
    public function destroy($id)
{
    try {
        // Find the intern by ID
        $intern = Internship::find($id);

        // Check if the intern exists
        if (!$intern) {
            return response()->json(['error' => 'Intern not found'], 404);
        }

        // Delete the intern profile
        $intern->delete();

        return response()->json(['message' => 'Intern deleted successfully'], 200);

    } catch (\Throwable $e) {
        // Log the error for debugging
        Log::error('Internship Delete Error', ['id' => $id, 'error' => $e->getMessage()]);

        // Return a generic error response
        return response()->json([
            'error' => 'An error occurred while deleting the intern profile.',
        ], 500);
    }
}

}
