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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('avatar')->nullable();
            $table->string('email')->unique();
            $table->text('description')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['user', 'admin', 'moderator', 'club', 'club_moderator', 'ban'])->default('user');
            $table->foreignId('club_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('club_banned')->default(false);
            $table->foreignId('club_ban_club_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('club_ban_reason')->nullable();
            $table->boolean('is_profile_private')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
