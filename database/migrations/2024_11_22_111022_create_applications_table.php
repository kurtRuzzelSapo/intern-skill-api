<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
      public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('internship_id'); // Foreign key to internships table
            $table->unsignedBigInteger('applicant_id'); // Foreign key to users table (applicants)
            $table->text('cover_letter')->nullable(); // Cover letter
            $table->string('resume')->nullable(); // Resume file path
            $table->string('status')->default('pending'); // Application status (e.g., pending, accepted, rejected)
            
            $table->timestamps(); // Created at and updated at

            // Foreign key constraints
            $table->foreign('internship_id')->references('id')->on('internships')->onDelete('cascade');
            $table->foreign('applicant_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
