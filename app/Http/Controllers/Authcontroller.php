<?php

namespace App\Http\Controllers;

use App\Models\InternProfile;
use App\Models\InternSkill;
use App\Models\RecruiterProfile;
use App\Models\Skill;
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
            // 'cover_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|nullable',
        ]);

        // Return validation errors
        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors(),
            ], 422);
        }

        try {
            // Define default profile image URL
            $defaultImage = Storage::url('images/profile_default.jpg');// Or storage path
            $coverImagePath = Storage::url('images/cover_default.jpg');// Or storage path might change this later

            // Create the user
            $user = User::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'profile_image' => $request->profile_image ?? $defaultImage,
                'password' => Hash::make($request->password),
                'role' => 'intern'
            ]);

            InternProfile::create([
                'user_id' => $user->id,
                'school' => $request->school,
                'degree' => $request->degree,
                'cover_image' => $coverImagePath ?? $defaultImage, // Store the file path in the database
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
    ]);

    // Return validation errors
    if ($validated->fails()) {
        Log::info('Validation Errors:', $validated->errors()->toArray()); // This is for Log validation errors
        return response()->json([
            'errors' => $validated->errors(),
        ], 422);
    }

    try {

          // Define default profile image URL
          $defaultImage = Storage::url('images/profile_default.jpg'); // Or storage path this will be the default YK

        // Create the user
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'profile_image' => $request->profile_image ?? $defaultImage,
            'password' => Hash::make($request->password),
            'role' => 'recruiter'
        ]);

        RecruiterProfile::create([
            'user_id' => $user->id,
            'company' => $request->company,
            'position' => $request->position,
            'industry' => $request->industry,
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
}
