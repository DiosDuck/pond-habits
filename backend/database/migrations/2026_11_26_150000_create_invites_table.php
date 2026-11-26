<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            // SHA-256 of the token. The plain token is only in the mail.
            $table->string('token_hash', 64)->unique();
            $table->foreignUuid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('UTC')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
        Schema::dropIfExists('invites');
    }
};
