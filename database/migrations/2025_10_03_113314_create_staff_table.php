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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('other_names');
            $table->string('password_hash');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say']);
            $table->string('mobile_phone');
            $table->string('staff_number')->nullable()->unique();
            $table->string('personal_email')->nullable();
            $table->timestamp('personal_email_verified_at')->nullable();
            $table->string('job_title')->nullable();
            $table->foreignId('department_id')->constrained();
            $table->enum('employment_type', ['permanent', 'contract', 'intern', 'consultant'])->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'supervisor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
