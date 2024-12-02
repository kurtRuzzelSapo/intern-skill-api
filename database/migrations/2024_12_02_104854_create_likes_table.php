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
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->integer('like_count')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Refferences for the user id
            $table->foreignId('forum_id')->constrained('forums')->onDelete('cascade'); // Refferences for the forum id
            $table->timestamps();

            $table->unique(['user_id', 'forum_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
