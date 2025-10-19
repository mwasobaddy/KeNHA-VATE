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
        Schema::create('idea_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate likes from same user on same idea
            $table->unique(['user_id', 'idea_id'], 'unique_user_idea_like');

            // Performance indexes
            $table->index(['idea_id', 'created_at'], 'idea_likes_idea_created_idx');
            $table->index(['user_id', 'created_at'], 'idea_likes_user_created_idx');
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate likes from same user on same comment
            $table->unique(['user_id', 'comment_id'], 'unique_user_comment_like');

            // Performance indexes
            $table->index(['comment_id', 'created_at'], 'comment_likes_comment_created_idx');
            $table->index(['user_id', 'created_at'], 'comment_likes_user_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('idea_likes');
    }
};