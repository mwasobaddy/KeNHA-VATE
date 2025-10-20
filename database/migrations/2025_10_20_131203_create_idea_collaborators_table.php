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
        Schema::create('idea_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('permission_level', ['suggest', 'edit'])->default('suggest');
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'removed'])->default('pending');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate collaborations
            $table->unique(['idea_id', 'user_id']);

            // Indexes for performance
            $table->index(['idea_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['invited_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_collaborators');
    }
};
