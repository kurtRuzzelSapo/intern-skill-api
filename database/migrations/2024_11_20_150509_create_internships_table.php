<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('internships', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('recruiter_id'); // Foreign key to recruiter_profiles
            $table->string('title'); // Internship title
            $table->text('desc'); // Description
            $table->text('requirements'); // Requirements
            $table->string('cover_post')->nullable(); // Cover image file path
            $table->string('location'); // Location
            $table->decimal('salary', 10, 2); // Salary
            $table->string('duration'); // Duration
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('recruiter_id')->references('id')->on('recruiter_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
