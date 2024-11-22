<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InternshipController extends Controller
{
    // Display a list of recruiter profiles
    public function index()
    {
        $interns = Internship::with('recruiter')->get();
        return response()->json($interns, 200);
    }

    // Create a post for Internship
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'recruiter_id' => 'required|exists:recruiter_profiles,id',
                'title' => 'required|string',
                'desc' => 'required|string',
                'requirements' => 'required|string',
                'cover_post' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
                'location' => 'required|string',
                'salary' => 'required|numeric',
                'duration' => 'required|string',
            ]);

            // Handle cover image upload
            $coverImagePath = null;
            if ($request->hasFile('cover_post')) {
                $coverImage = $request->file('cover_post');
                // Store image in the 'public/images' directory and get the file path
                $coverImagePath = $coverImage->store('images', 'public');
            }

            // Create the intern profile with the file path of the cover image
            $intern = Internship::create([
                'recruiter_id' => $request->recruiter_id,
                'title' => $request->title,
                'desc' => $request->desc,
                'requirements' => $request->requirements,
                'cover_post' => $coverImagePath, // Store the file path in the database
                'location' => $request->location,
                'salary' => $request->salary,
                'duration' => $request->duration,
            ]);

            return response()->json($intern, 201);

        } catch (Exception $e) {
            Log::error('Internship Creation Error: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while creating the Internship.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Show a specific intern profile
    public function show($id)
    {
        try {
            $recruiter = Internship::with('recruiter')->find($id);

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
                'recruiter_id' => $validatedData['recruiter_id'],
                'title' => $validatedData['title'],
                'desc' => $validatedData['desc'],
                'requirements' => $validatedData['requirements'],
                'cover_post' => $coverPostPath, // Save the updated cover image path
                'location' => $validatedData['location'],
                'salary' => $validatedData['salary'],
                'duration' => $validatedData['duration'],
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
