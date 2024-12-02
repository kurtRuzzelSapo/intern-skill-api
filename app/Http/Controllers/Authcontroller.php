<?php

namespace App\Http\Controllers;

use App\Models\InternProfile;
use App\Models\InternSkill;
use App\Models\RecruiterProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
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
                'profile_image' => $request->profile_image ?? $defaultImage,
                'password' => Hash::make($request->password),
                'role' => 'intern'
            ]);

            InternProfile::create([
                'user_id' => $user->id,
                'school' => $request->school,
                'degree' => $request->degree,
                'cover_image' => $coverImagePath, // Store the file path in the database
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
            'profile_image' => $request->profile_image ?? $defaultImage,
            'password' => Hash::make($request->password),
            'role' => 'recruiter'
        ]);

        RecruiterProfile::create([
            'user_id' => $user->id,
            'company' => $request->company,
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
