<?php

namespace App\Http\Controllers;

use App\Models\InternSkill;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SkillController extends Controller
{
    public function index()
    {
        try {
            $skills = Skill::all();
            return response()->json($skills, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching skills: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while fetching skills.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate request inputs
            $request->validate([
                'skill_name' => 'required|string|max:255',
                'intern_id' => 'required|exists:users,id', // Validate that intern_id exists in the users table
            ]);

            // Create skill
            $skill = Skill::create([
                'skill_name' => $request->skill_name,
            ]);

            // Link skill to the intern
            $internSkill = InternSkill::create([
                'skill_id' => $skill->id,
                'intern_id' => $request->intern_id,
            ]);

            // Return success response
            return response()->json([
                'message' => 'Skill created successfully.',
                'skill' => $skill,
                'intern_skill' => $internSkill,
            ], 201);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error creating skill: ', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json([
                'error' => 'An error occurred while creating the skill.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Skill $skill)
    {
        try {
            return response()->json($skill, 200);
        } catch (\Exception $e) {
            Log::error('Error showing skill: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while fetching the skill.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, Skill $skill){
    //     try {
    //         $request->validate([
    //             'skill_name' => 'required|string|max:255',
    //         ]);

    //         $skill->update([
    //             'skill_name' => $request->skill_name,
    //         ]);

    //         return response()->json($skill, 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error updating skill: ', ['message' => $e->getMessage()]);
    //         return response()->json([
    //             'error' => 'An error occurred while updating the skill.',
    //             'details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Skill $skill)
    {
        try {
            $skill->delete();
            return response()->json(['message' => 'Skill deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting skill: ', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'An error occurred while deleting the skill.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
