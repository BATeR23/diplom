<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The role column is already a string/varchar, so it should support 'admin' value
        // This migration is mainly for documentation purposes
        // If the column doesn't exist or needs to be updated, we'll handle it here
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                // Column exists, ensure it's a string type that can hold 'admin'
                // For SQL Server, if it's an enum, we'd need to drop and recreate
                // But since it's already a string in the previous migration, we're good
            } else {
                // If role column doesn't exist, create it
                $table->string('role')->default('passenger')->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to enum if needed (but we'll keep it as string for flexibility)
        // This migration is mostly for ensuring admin role is supported
    }
};

