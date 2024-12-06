<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Models\RecruiterProfile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RecruiterController extends Controller
{
    // Display a list of recruiter profiles
    public function index()
    {
        $interns = RecruiterProfile::with('user')->get();
        return response()->json($interns, 200);
    }


    public function store(Request $request)
    {

    }

    // Show a specific intern profile
    public function show($id)
    {
        try {
            $recruiter = RecruiterProfile::with('user')->find($id);


            if (!$recruiter) {
                return response()->json(['error' => 'Recruiter not found'], 404);
            }

            return response()->json($recruiter, 200);
        } catch (Exception $e) {
            Log::error('Intern Profile Show Error: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while retrieving the intern profile.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing intern profile
    public function update(Request $request, $id)
    {
        try {
            // Find the internship record
            $recruiter = RecruiterProfile::find($id);
            $user = User::find($id);

            if (!$recruiter) {
                return response()->json(['error' => 'Recruiter not found'], 404);
            }

            // Validate incoming request
            $validatedData = $request->validate([
                'company' => 'required|string',
                 'position' => 'required|string',
                  'industry' => 'required|string',

                    'fullname'=> 'nullable|string',
                  'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
                  'phone_number' => 'nullable|numeric', // Changed from int to numeric for better compatibility
                  'address' => 'nullable|string',
            ]);

            // Handle cover_post upload if provided
            $profileImagePath = $user->profile_image; // Keep the old profile image by default

            if ($request->hasFile('profile_image')) {
                // Delete old profile image if exists
                if ($profileImagePath && Storage::exists('public/' . $profileImagePath)) {
                    Storage::delete('public/' . $profileImagePath);
                }
                $profileImagePath = $request->file('profile_image')->store('images', 'public');
            }

            $user->update([
                'phone_number' => $request->phone_number ?? $user->phone_number,
                'address' => $request->address ?? $user->address,
                'fullname' => $request->fullname ?? $user->fullname,
                'profile_image' => $profileImagePath,
            ]);

            // Update the recruiter record with validated data
            $recruiter->update([
               'company' => $request->company ?? $recruiter->company,
               'position' => $request->position ?? $recruiter->position,
               'industry' => $request->industry ?? $recruiter->industry,
            ]);

            return response()->json([$recruiter, $user], 200);

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


    public function getRecruiterInternships()
{
    // Get the recruiter ID of the authenticated user
    $recruiterId = Auth::id();

    // Fetch internships belonging to the recruiter
    $internships = Internship::where('recruiter_id', $recruiterId)->get();

    // Return the internships as JSON or a view
    return response()->json([
        'message' => 'Recruiter internships retrieved successfully.',
        'internships' => $internships,
    ], 200);
}


    // Delete an intern profile
      public function destroy($id)
      {
          try {
              $intern = Internship::find($id);

              if (!$intern) {
                  return response()->json(['error' => 'Intern not found'], 404);
              }

              // Delete intern profile
              $intern->delete();

              return response()->json(['message' => 'Intern deleted successfully'], 200);
          } catch (Exception $e) {
              Log::error('Internship Delete Error: ', ['message' => $e->getMessage()]);
              return response()->json([
                  'error' => 'An error occurred while deleting the intern profile.',
                  'details' => $e->getMessage()
              ], 500);
          }
      }
}
