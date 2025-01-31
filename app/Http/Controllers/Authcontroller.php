<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\InternProfile;
use App\Models\InternSkill;
use App\Models\RecruiterProfile;
use App\Models\Skill;
use App\Models\specialization;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Authcontroller extends Controller
{
    //LOGIN
    public function login(Request $request){
        $validated = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',

            
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors(),403);
        }
        $credentials = ['email' => $request->email, 'password' => $request->password];

        try {
            if (!auth()->attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'],403);
            }
            $user = User::where('email',$request->email)->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;
            //return
            return response()->json([
                'access_token' => $token,
                'user' => $user
            ],200);

        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()],403);
        }
    }

    public function getMyData($id)
    {
        try {
            // Find the user by ID
            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Check the user's role and retrieve the appropriate profile
            if ($user->role === 'recruiter') {
                $recruiterProfile = RecruiterProfile::with('user')->where('user_id', $user->id)->first();

                if (!$recruiterProfile) {
                    return response()->json(['error' => 'No recruiter profile found for this user.'], 404);
                }

                return response()->json([
                    'user' => $user,
                    'recruiter_profile' => $recruiterProfile,
                ], 200);
            } elseif ($user->role === 'intern') {
                $internProfile = InternProfile::with('user')->where('user_id', $user->id)->first();

                if (!$internProfile) {
                    return response()->json(['error' => 'No intern profile found for this user.'], 404);
                }

                if ($internProfile->resume) {
                    $internProfile->resume_url = url('storage/' . $internProfile->resume);
                }

                return response()->json([
                    'user' => $user,
                    'intern_profile' => $internProfile,
                ], 200);
            }

            // Invalid role
            return response()->json(['error' => 'Invalid user role.'], 400);

        } catch (Exception $e) {
            Log::error('Error fetching user data: ', [
                'message' => $e->getMessage(),
                'user_id' => $id, // Log the user ID passed
            ]);

            return response()->json([
                'error' => 'An error occurred while retrieving the data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    //INTERN REGISTER
    public function registerIntern(Request $request)
    {
        // Validations
        $validated = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|max:255|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'school' => 'required|string',
            'gender' => 'required|string',
            'hobbies' => 'required|string',
            'interest' => 'required|string',
            'specialization' => 'required|array',
            'specialization.*' => 'required|string',
        ]);

        // Return validation errors
        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors(),
            ], 422);
        }

        try {
            // Define arrays of random default images
            $profileImages = [
                Storage::url('images/profile_default_1.jpg'),
                Storage::url('images/profile_default_2.jpg'),
                Storage::url('images/profile_default_3.jpg'),
                Storage::url('images/profile_default_4.jpg'),
            ];

            $coverImages = [
                Storage::url('images/cover_default_1.jpg'),
                Storage::url('images/cover_default_2.jpg'),
                Storage::url('images/cover_default_3.jpg'),
                Storage::url('images/cover_default_4.jpg'),
            ];

            $resume = Storage::url('images/blank_resume.jpg');

            // Pick a random image from each array
            $randomProfileImage = $profileImages[mt_rand(0, count($profileImages) - 1)];
            $randomCoverImage = $coverImages[mt_rand(0, count($coverImages) - 1)];

            // Create the user
            $user = User::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'interest' => $request->interest,
                'hobbies' => $request->hobbies,
                'profile_image' => $request->profile_image ?? $randomProfileImage,
                'password' => Hash::make($request->password),
                'role' => 'intern'
            ]);


                foreach ($validated->validated()['specialization'] as $specialization) {

                specialization::create([
                    'user_id' => $user->id,
                    'specialization' => strtolower(trim($specialization)),
                ]);
            }

            // Create the intern profile
            InternProfile::create([
                'user_id' => $user->id,
                'school' => $request->school,
                'degree' => $request->degree,
                'cover_image' => $randomCoverImage, // Store the random cover image path in the database
                'about' => 'Hello, I am ' . $request->fullname . ' a student at ' . $request->school,
                'resume' => $resume
            ]);

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => "You're successfully registered as Intern",
                'access_token' => $token,
                'user' => $user,
            ], 201);

        } catch (\Exception $exception) {
            Log::error('Registration Error:', ['message' => $exception->getMessage()]); // Log exception
            return response()->json([
                'error' => $exception->getMessage(),
            ], 500);
        }
    }


    // RECRUITER REGISTER
    public function registerRecruiter(Request $request)
    {
    // Log::info('Incoming Request:', $request->all());

    // Validations
    $validated = Validator::make($request->all(), [
        'fullname' => 'required|string|max:255',
        'email' => 'required|string|max:255|email|unique:users',
        'company' => 'required|string|max:255',
        'password' => 'required|string|min:6|confirmed',
        'gender' => 'required|string',
        'position' => 'required|string',
        'industry' => 'required|string',
        'hobbies' => 'required|string',
        'interest' => 'required|string',
        'specialization' => 'required|array',
        'specialization.*' => 'required|string',
    ]);

    // Return validation errors
    if ($validated->fails()) {
        return response()->json([
            'errors' => $validated->errors(),
        ], 422);
    }



        try {
            // Define arrays of random default images
            $profileImages = [
                Storage::url('images/profile_default_1.jpg'),
                Storage::url('images/profile_default_2.jpg'),
                Storage::url('images/profile_default_3.jpg'),
                Storage::url('images/profile_default_4.jpg'),
            ];

            $coverImages = [
                Storage::url('images/cover_default_1.jpg'),
                Storage::url('images/cover_default_2.jpg'),
                Storage::url('images/cover_default_3.jpg'),
                Storage::url('images/cover_default_4.jpg'),
            ];




              // Pick a random image from each array
              $randomProfileImage = $profileImages[mt_rand(0, count($profileImages) - 1)];
              $randomCoverImage = $coverImages[mt_rand(0, count($coverImages) - 1)];



        // Create the user
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'hobbies' => $request->hobbies,
            'profile_image' => $request->profile_image ?? $randomProfileImage,
            'password' => Hash::make($request->password),
            'role' => 'recruiter'
        ]);

        foreach ($validated->validated()['specialization'] as $specialization) {
            specialization::create([
                'user_id' => $user->id,
                'specialization' => strtolower(trim($specialization)),
            ]);
        }

        RecruiterProfile::create([
            'user_id' => $user->id,
            'company' => $request->company,
            'position' => $request->position,
            'industry' => $request->industry,
            'cover_image' => $randomCoverImage, // Store the random cover image path in the database
            'about' => 'Hello, I am ' . $request->fullname . ' working at ' . $request->school. $request->industry .'Industry',
        ]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => "You're successfully registered as recruiter",
            'access_token' => $token,
            'user' => $user,
        ], 201);

    } catch (\Exception $exception) {
        Log::error('Registration Error:', ['message' => $exception->getMessage()]); // Log exception
        return response()->json([
            'error' => $exception->getMessage(),
        ], 500);
    }
}


public function updateResume(Request $request)
{
    // Logging the request data as an array
    Log::info('Request Data:', ['data' => $request->all()]); // Log request data

    // Check if 'resume' file is present
    if ($request->hasFile('resume')) {
        Log::info('File Uploaded:', ['file' => $request->file('resume')->getClientOriginalName()]); // Log the file name
    } else {
        Log::info('No file uploaded.');
    }

    // Validate the request
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'resume' => 'required|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:2048',
    ]);

    try {
        // Fetch the existing intern profile
        $internProfile = InternProfile::where('user_id', $request->user_id)->first();

        if (!$internProfile) {
            return response()->json(['message' => 'Intern profile not found.'], 404);
        }

        // If there's an existing resume, delete it from storage
        if ($internProfile->resume) {
            Storage::disk('public')->delete($internProfile->resume);
            Log::info('Old Resume Deleted:', ['path' => $internProfile->resume]);
        }

        // Store the new resume file
        $path = $request->file('resume')->store('resumes', 'public');


        // Update the intern profile with the new resume path
        $internProfile->update(['resume' => $path]);

        // Returning response with the resume URL
        Log::info('Resume Updated Successfully:', ['resume_url' => Storage::url($path)]);

        return response()->json([
            'message' => 'Resume updated successfully.',
            'resume_url' => Storage::url($path),
        ], 200);

    } catch (\Exception $exception) {
        Log::error('Error updating resume:', ['message' => $exception->getMessage()]);
        return response()->json(['error' => 'Failed to update resume.'], 500);
    }
}




public function updateProfile(Request $request)
{
    try {
        // Validate the incoming request
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:15',
            'about' => 'nullable|string',
            'school' => 'nullable|string',
            'company' => 'nullable|string',
        ]);

        // Fetch the user
        $user = User::find($validatedData['user_id']);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update user details
        $user->update([
            'fullname' => $validatedData['name'],
            'email' => $validatedData['email'],
            'address' => $validatedData['address'],
            'phone_number' => $validatedData['phone_number'],
        ]);

        // Update recruiter or intern profile if applicable
        if ($user->role === 'recruiter') {
            $recruiterProfile = RecruiterProfile::firstOrCreate(['user_id' => $user->id]);
            $recruiterProfile->update([
                'about' => $validatedData['about'],
                'company' => $validatedData['company'],
            ]);
        } elseif ($user->role === 'intern') {
            $internProfile = InternProfile::firstOrCreate(['user_id' => $user->id]);
            $internProfile->update([
                'about' => $validatedData['about'],
                'school' => $validatedData['school'],
            ]);
        }


        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('recruiterProfile', 'internProfile'), // Eager load related profiles
        ], 200);
    } catch (\Throwable $th) {
        // Log the error for debugging
        Log::error('Profile update failed', [
            'error' => $th->getMessage(),
            'trace' => $th->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'Failed to update profile',
            'error' => $th->getMessage(),
        ], 500);
    }
}

public function getSpecialization()
{
    try {
        // Retrieve unique specializations
        $specializations = Specialization::select('specialization')
            ->distinct()
            ->get();

        // Return the data in JSON format
        return response()->json([
            'success' => true,
            'data' => $specializations
        ], 200);
    } catch (\Exception $exception) {
        // Handle exceptions and log errors
        Log::error('Error retrieving specializations:', ['message' => $exception->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching specializations.'
        ], 500);
    }

}






}
