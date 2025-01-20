<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\InternProfile;
use App\Models\Internship;
use App\Models\RecruiterProfile;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Support\Facades\Notification;

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


    public function getMyData()
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Check the user's role
        if ($user->role === 'recruiter') {
            // Fetch the recruiter's profile
            $recruiterProfile = RecruiterProfile::with('user')->where('user_id', $user->id)->first();

            if (!$recruiterProfile) {
                return response()->json(['error' => 'No recruiter profile found for this user.'], 404);
            }

            return response()->json([
                'user' => $user,
                'recruiter_profile' => $recruiterProfile,
            ], 200);

        } elseif ($user->role === 'intern') {
            // Fetch the intern's profile
            $internProfile = InternProfile::with('user')->where('user_id', $user->id)->first();

            if (!$internProfile) {
                return response()->json(['error' => 'No intern profile found for this user.'], 404);
            }

            return response()->json([
                'user' => $user,
                'intern_profile' => $internProfile,
            ], 200);
        }

        // If the role is neither recruiter nor intern
        return response()->json(['error' => 'Invalid user role.'], 400);

    } catch (Exception $e) {
        Log::error('Error fetching user data: ', [
            'message' => $e->getMessage(),
            'user_id' => Auth::id() // Log the user ID if available
        ]);

        return response()->json([
            'error' => 'An error occurred while retrieving the data.',
            'details' => $e->getMessage(),
        ], 500);
    }
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


//     public function getRecruiterInternships()
// {
//     // Get the recruiter ID of the authenticated user
//     $recruiterId = Auth::id();

//     // Fetch internships belonging to the recruiter
//     $internships = Internship::where('recruiter_id', $recruiterId)->get();

//     // Return the internships as JSON or a view
//     return response()->json([
//         'message' => 'Recruiter internships retrieved successfully.',
//         'internships' => $internships,
//     ], 200);
// }

public function getRecruiterInternshipsById($recruiterId)
{
    try {
        // Check if the recruiter profile exists
        $recruiterProfile = RecruiterProfile::find($recruiterId);

        if (!$recruiterProfile) {
            return response()->json(['error' => 'Recruiter profile not found'], 404);
        }

        // Get all internships created by the recruiter with their applications
        $internships = Internship::with([
            'applications' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'applications.applicant',
            'applications.applicant.internProfile'
        ])
            ->where('recruiter_id', $recruiterId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($internship) {
                return [
                    'id' => $internship->id,
                    'title' => $internship->title,
                    'description' => $internship->description,
                    'location' => $internship->location,
                    'salary' => $internship->salary,
                    'requirements' => $internship->requirements,
                    'created_at' => $internship->created_at,
                    'applications_count' => $internship->applications->count(),
                    'applications' => $internship->applications->map(function ($application) {
                        return [
                            'id' => $application->id,
                            'status' => $application->status,
                            'created_at' => $application->created_at,
                            'cover_letter' => $application->cover_letter,
                            'resume' => $application->resume,
                            'intern' => [
                                'id' => $application->applicant->id,
                                'name' => $application->applicant->fullname,
                                'email' => $application->applicant->email,
                                'phone' => $application->applicant->phone_number,
                                'address' => $application->applicant->address,
                                'profile_image' => $application->applicant->profile_image,
                                'education' => [
                                    'school' => $application->applicant->internProfile->school ?? null,
                                    'degree' => $application->applicant->internProfile->degree ?? null,
                                    'gpa' => $application->applicant->internProfile->gpa ?? null,
                                ],
                                'about' => $application->applicant->internProfile->about ?? null,
                                'cover_image' => $application->applicant->internProfile->cover_image ?? null,
                            ]
                        ];
                    })
                ];
            });

        return response()->json([
            'internships' => $internships
        ], 200);

    } catch (Exception $e) {
        Log::error('Error fetching recruiter internships: ' . $e->getMessage());
        return response()->json([
            'error' => 'An error occurred while retrieving the internships.',
            'details' => $e->getMessage()
        ], 500);
    }
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

public function updateApplicationStatus(Request $request, $applicationId)
{
    try {
        // Validate the request
        $validatedData = $request->validate([
            'status' => 'required|string|in:accepted,rejected'
        ]);

        // Find the application
        $application = Application::with(['internship.recruiter', 'applicant'])->findOrFail($applicationId);

        // Check if the authenticated recruiter owns the internship
        $internship = $application->internship;
        if ($internship->recruiter_id != $request->user()->recruiterProfile->id) {
            return response()->json([
                'error' => 'Unauthorized to update this application'
            ], 403);
        }

        // Update the application status
        $application->update([
            'status' => $validatedData['status']
        ]);

        // Send notification to the applicant
        $application->applicant->notify(new ApplicationStatusUpdated($application));

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Application not found'
        ], 404);
    } catch (Exception $e) {
        Log::error('Application Status Update Error: ', ['message' => $e->getMessage()]);
        return response()->json([
            'error' => 'An error occurred while updating the application status',
            'details' => $e->getMessage()
        ], 500);
    }
}
}
