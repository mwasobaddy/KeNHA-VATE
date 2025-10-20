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
        Schema::create('idea_collaboration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collaborator_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('request_message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('response_at')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['idea_id', 'status']);
            $table->index(['collaborator_user_id', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_collaboration_requests');
    }
};
