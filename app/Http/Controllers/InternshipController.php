<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Internship;
use App\Models\InternshipSkill;
use App\Models\RecruiterProfile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

//This is for creating the Internship for Intern who wants to apply
class InternshipController extends Controller
{
    // Display a list of recruiter profiles
    public function index()
{
    $interns = Internship::with(['recruiter.user', 'skills', 'applications.applicant.user'])->get();

    return response()->json($interns->map(function ($intern) {
        $intern->resume = $intern->resume ? url('storage/' . $intern->resume) : null;
        return $intern;
    }), 200);
}




public function getMyInternships($recruiter_id)
{
    try {
        // Verify the recruiter exists
        $recruiterProfile = RecruiterProfile::where('user_id', $recruiter_id)->first();

        if (!$recruiterProfile) {
            return response()->json(['error' => 'Recruiter profile not found'], 404);
        }

        // Retrieve internships created by the recruiter
        $internships = Internship::with([
            'recruiter.user',
            'skills',
            'applications.applicant'
        ])
        ->where('recruiter_id', $recruiterProfile->id)
        ->latest()
        ->get();

        // Append full resume URL for each application
        $internships->each(function ($internship) {
            $internship->applications->each(function ($application) {
                $application->resume = $application->resume ? url('storage/' . $application->resume) : null;
            });
        });

        return response()->json([
            'internships' => $internships,
        ], 200);

    } catch (Exception $e) {
        Log::error('Error fetching recruiter internships: ', [
            'message' => $e->getMessage(),
            'recruiter_id' => $recruiter_id,
        ]);

        return response()->json([
            'error' => 'An error occurred while retrieving the internships.',
            'details' => $e->getMessage(),
        ], 500);
    }
}





    public function store(Request $request)
{
    // Validate the request data
    $validated = $request->validate([
        'recruiter_id' => 'required|exists:recruiter_profiles,id',
        'title' => 'required|string',
        'desc' => 'required|string',
        'location' => 'required|string',
        'salary' => 'nullable|numeric|min:0|max:1000000',
        'category' => 'required|string',
        'start_status' => 'required|string',
        'deadline' => 'required|date',
        'start' => 'required|date',
        'end' => 'required|date',
        'skill' => 'required|array',
        'skill.*' => 'required|string',
    ]);

    try {
        // Start transaction
        DB::beginTransaction();

        // Create the internship
        $internship = Internship::create([
            'recruiter_id' => $validated['recruiter_id'],
            'title' => $validated['title'],
            'desc' => $validated['desc'],
            'location' => $validated['location'],
            'salary' => $validated['salary'],
            'category' => $validated['category'],
            'start_status' => $validated['start_status'],
            'deadline' => $validated['deadline'],
            'start' => $validated['start'],
            'end' => $validated['end'],
        ]);

        // Add skills to the internship
        foreach ($validated['skill'] as $skill) {
            InternshipSkill::create([
                'internship_id' => $internship->id,
                'skill' => strtolower(trim($skill)),
            ]);
        }

        // Fetch recruiter profile and user details
        $recruiterProfile = RecruiterProfile::with('user')->find($validated['recruiter_id']);

        // Commit transaction
        DB::commit();

        // Return success response
        return response()->json([
            'id' => $internship->id,
            'title' => $internship->title,
            'desc' => $internship->desc,
            'location' => $internship->location,
            'salary' => number_format($internship->salary, 2),
            'category' => $internship->category,
            'start_status' => $internship->start_status,
            'deadline' => $internship->deadline,
            'start' => $internship->start,
            'end' => $internship->end,
            'created_at' => $internship->created_at,
            'updated_at' => $internship->updated_at,
            'recruiter' => [
                'id' => $recruiterProfile->id,
                'company' => $recruiterProfile->company,
                'industry' => $recruiterProfile->industry,
                'position' => $recruiterProfile->position,
                'about' => $recruiterProfile->about,
                'cover_image' => $recruiterProfile->cover_image,
                'created_at' => $recruiterProfile->created_at,
                'updated_at' => $recruiterProfile->updated_at,
                'user' => [
                    'id' => $recruiterProfile->user->id,
                    'fullname' => $recruiterProfile->user->fullname,
                    'email' => $recruiterProfile->user->email,
                    'phone_number' => $recruiterProfile->user->phone_number,
                    'address' => $recruiterProfile->user->address,
                    'gender' => $recruiterProfile->user->gender,
                    'role' => $recruiterProfile->user->role,
                    'profile_image' => $recruiterProfile->user->profile_image,
                    'created_at' => $recruiterProfile->user->created_at,
                    'updated_at' => $recruiterProfile->user->updated_at,
                ],
            ],
        ], 201);

    } catch (\Exception $e) {
        // Rollback transaction on error
        DB::rollBack();

        // Log the exception
        Log::error('Error creating internship: ' . $e->getMessage());

        // Return error response
        return response()->json([
            'error' => 'An error occurred while creating the internship.',
            'details' => $e->getMessage(),
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
