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
        Schema::create('idea_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('changed_fields'); // Only store what changed, not full data
            $table->text('change_summary')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('revision_type', ['author', 'collaborator', 'rollback'])->default('author');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            // Indexes for performance
            $table->index(['idea_id', 'revision_number']);
            $table->index(['created_by_user_id']);
            $table->index(['status']);
            $table->index(['idea_id', 'status']); // For filtering revisions by status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_revisions');
    }
};
