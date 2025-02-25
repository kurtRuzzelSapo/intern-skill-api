<?php
namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\InternProfile;
use App\Models\Internship;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class InternController extends Controller
{
    // Display a list of intern profiles
    public function index()
    {
        $interns = InternProfile::with('user')->get();
        return response()->json($interns, 200);
    }


    public function getMyData()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Retrieve the user's intern profile, including the associated user data
            $internProfile = InternProfile::with('user')->where('user_id', $user->id)->first();

            // Check if the user has an associated intern profile
            if (!$internProfile) {
                return response()->json(['error' => 'No intern profile found for this user.'], 404);
            }

            // Return the authenticated user's data and their intern profile
            return response()->json([
                'user' => $user,
                'intern_profile' => $internProfile,
            ], 200);

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


    // Store a new intern profile
    // public function store(Request $request)
    // {
    //     try {
    //         // Validate the request data
    //         $request->validate([
    //             'user_id' => 'required|exists:users,id',
    //             'school' => 'required|string',
    //             'degree' => 'required|string',
    //             'cover_letter' => 'required|string',
    //             'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
    //             'phone_number' => 'required|int',
    //             'address' => 'required|string',
    //             'school' => 'required|string',
    //             'degree' => 'required|string',
    //             'resume' => 'required|string',
    //             'gpa' => 'nullable|numeric',
    //             'about' => 'nullable|string',
    //         ]);

    //         // Handle cover image upload
    //         $coverImagePath = null;
    //         if ($request->hasFile('cover_image')) {
    //             $coverImage = $request->file('cover_image');
    //             // Store image in the 'public/images' directory and get the file path
    //             $coverImagePath = $coverImage->store('images', 'public');
    //         }
    //         $user = User::create([
    //             'phone_number'=> $request->phone_number,
    //             'address'=> $request->address,
    //         ]);
    //         // Create the intern profile with the file path of the cover image
    //         $intern = InternProfile::create([
    //             'user_id' => $request->user_id,
    //             'school' => $request->school,
    //             'degree' => $request->degree,
    //             'cover_letter' => $request->cover_letter,
    //             'cover_image' => $coverImagePath, // Store the file path in the database
    //             'resume' => $request->resume,
    //             'gpa' => $request->gpa,
    //             'about' => $request->about,
    //         ]);

    //         return response()->json([$intern,$user], 201);

    //     } catch (Exception $e) {
    //         Log::error('Intern Profile Store Error: ', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'error' => 'An error occurred while storing the intern profile.',
    //             'details' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // Show a specific intern profile
    public function show($id)
    {
        try {
            $intern = InternProfile::with('user')->find($id);

            if (!$intern) {
                return response()->json(['error' => 'Intern not found'], 404);
            }

            return response()->json($intern, 200);
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
        // Find the intern profile and user profile
        $intern = InternProfile::find($id);
        $user = User::find($id);

        if (!$intern || !$user) {
            return response()->json(['error' => 'Intern or User not found'], 404);
        }

        // Validate incoming request
        $request->validate([
            'school' => 'nullable|string',
            'degree' => 'nullable|string',
            'cover_letter' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
            'phone_number' => 'nullable|numeric', // Changed from int to numeric for better compatibility
            'address' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx',
            'gpa' => 'nullable|numeric',
            'about' => 'nullable|string',
            'fullname' => 'nullable|string',

        ]);

        // Handle file uploads
        $coverImagePath = $intern->cover_image;  // Keep the old cover image by default
        $profileImagePath = $user->profile_image; // Keep the old profile image by default

        if ($request->hasFile('cover_image')) {
            // Delete old cover image if exists
            if ($coverImagePath && Storage::exists('public/' . $coverImagePath)) {
                Storage::delete('public/' . $coverImagePath);
            }
            $coverImagePath = $request->file('cover_image')->store('images', 'public');
        }

        if ($request->hasFile('profile_image')) {
            // Delete old profile image if exists
            if ($profileImagePath && Storage::exists('public/' . $profileImagePath)) {
                Storage::delete('public/' . $profileImagePath);
            }
            $profileImagePath = $request->file('profile_image')->store('images', 'public');
        }

        // Update the User table
        $user->update([
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'address' => $request->address ?? $user->address,
            'fullname' => $request->fullname ?? $user->fullname,
            'profile_image' => $profileImagePath,
        ]);

        // Update the InternProfile table
        $intern->update([
            'school' => $request->school ?? $intern->school,
            'degree' => $request->degree ?? $intern->degree,
            'cover_letter' => $request->cover_letter ?? $intern->cover_letter,
            'cover_image' => $coverImagePath,
            'resume' => $request->resume ?? $intern->resume,
            'gpa' => $request->gpa ?? $intern->gpa,
            'about' => $request->about ?? $intern->about,
        ]);

        return response()->json([
            'user' => $user,
            'intern_profile' => $intern,
        ], 200);
    } catch (Exception $e) {
        Log::error('Intern Profile Update Error: ', ['message' => $e->getMessage()]);
        return response()->json([
            'error' => 'An error occurred while updating the intern profile.',
            'details' => $e->getMessage(),
        ], 500);
    }
}



public function applyForInternship(Request $request)
{
    try {
        // Get the authenticated user

        // Validate incoming data
        $validated = $request->validate([
            'internship_id' => 'required|exists:internships,id',
            'applicant_id' => 'required',
            'resume' => 'required|string',
        ]);



        // Handle resume upload
        $resumePath = null;
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $resumePath = $file->store('resumes', 'public');
        }

        // Generate URL for the uploaded resume
        $resumeUrl = $resumePath ? Storage::url($resumePath) : null;

        // Check for existing application
        $existingApplication = Application::where('internship_id', $validated['internship_id'])
            ->where('applicant_id', $request->applicant_id)
            ->first();

        if ($existingApplication) {
            return response()->json(['error' => 'You have already applied for this internship.'], 409);
        }

        // Create the application record with the resume URL
        $application = Application::create([
            'internship_id' => $request->internship_id,
            'applicant_id' => $request->applicant_id,
            'resume' => $request->resume, // Store the URL here
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Your application has been submitted successfully.',
            'application' => $application,
            'resume_url' => $resumeUrl,  // Return the URL of the uploaded resume
        ], 201);

    } catch (Exception $e) {
        return response()->json([
            'error' => 'An error occurred while submitting your application.',
            'details' => $e->getMessage()
        ], 500);
    }
}



public function showMyApplications()
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        // Add debug logging
        Log::info('User attempting to view applications:', [
            'user_id' => $user ? $user->id : 'no user',
            'token' => request()->bearerToken()
        ]);

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Retrieve applications with related internship data
        $applications = Application::with(['internship', 'applicant'])
            ->where('applicant_id', $user->id)
            ->orderBy('created_at', 'desc')  // Optional: sort by newest first
            ->get();

        // If no applications found, return empty array instead of error
        if ($applications->isEmpty()) {
            return response()->json([
                'applications' => [],
                'message' => 'No applications found'
            ], 200);
        }

        return response()->json([
            'applications' => $applications,
        ], 200);

    } catch (Exception $e) {
        Log::error('Error fetching applications: ' . $e->getMessage());
        return response()->json([
            'error' => 'An error occurred while retrieving your applications.',
            'details' => $e->getMessage()
        ], 500);
    }
}

// public function showApplication($id)
// {
//     try {
//         $application = Application::with(['internship', 'applicant'])
//             ->find($id);

//         if (!$application) {
//             return response()->json(['error' => 'Application not found'], 404);
//         }

//         return response()->json($application, 200);
//     } catch (Exception $e) {
//         Log::error('Application Show Error: ', ['message' => $e->getMessage()]);
//         return response()->json([
//             'error' => 'An error occurred while retrieving the application.',
//             'details' => $e->getMessage()
//         ], 500);
//     }
// }



    // Delete an intern profile
      public function destroy($id)
      {
          try {
              $intern = InternProfile::find($id);

              if (!$intern) {
                  return response()->json(['error' => 'Intern not found'], 404);
              }

              // Delete intern profile
              $intern->delete();

              return response()->json(['message' => 'Intern deleted successfully'], 200);
          } catch (Exception $e) {
              Log::error('Intern Profile Delete Error: ', ['message' => $e->getMessage()]);
              return response()->json([
                  'error' => 'An error occurred while deleting the intern profile.',
                  'details' => $e->getMessage()
              ], 500);
          }
      }


}

