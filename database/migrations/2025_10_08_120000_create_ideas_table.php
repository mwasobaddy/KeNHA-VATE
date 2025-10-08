<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('idea_title', 255);
            $table->string('slug')->unique();
            $table->unsignedBigInteger('thematic_area_id')->nullable();
            $table->text('abstract')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('proposed_solution')->nullable();
            $table->text('cost_benefit_analysis')->nullable();
            $table->text('declaration_of_interests')->nullable();
            $table->boolean('original_idea_disclaimer')->default(false);
            $table->boolean('collaboration_enabled')->default(false);
            $table->boolean('team_effort')->default(false);
            $table->json('team_members')->nullable();

            // Idea status: draft or submitted
            $table->enum('status', ['draft', 'submitted'])->default('draft');

            // Attachment stored as binary blob in DB
            // Use ->binary() for medium/long blobs depending on DB driver
            $table->binary('attachment')->nullable();
            $table->string('attachment_filename')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
