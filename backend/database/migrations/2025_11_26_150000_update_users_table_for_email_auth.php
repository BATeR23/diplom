<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique()->after('phone');
            }

            if (!Schema::hasColumn('users', 'password')) {
                $table->string('password')->after('email');
            }

            if (Schema::hasColumn('users', 'login_code')) {
                $table->dropColumn('login_code');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['driver', 'passenger'])
                    ->default('passenger')
                    ->after('password');
            }

            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('avatar_url');
            }

            if (!Schema::hasColumn('users', 'rating_average')) {
                $table->decimal('rating_average', 3, 2)->default(0)->after('bio');
            }

            if (!Schema::hasColumn('users', 'rides_completed')) {
                $table->unsignedInteger('rides_completed')->default(0)->after('rating_average');
            }

            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable()->after('rides_completed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferences')) {
                $table->dropColumn('preferences');
            }

            if (Schema::hasColumn('users', 'rides_completed')) {
                $table->dropColumn('rides_completed');
            }

            if (Schema::hasColumn('users', 'rating_average')) {
                $table->dropColumn('rating_average');
            }

            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }

            if (Schema::hasColumn('users', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (!Schema::hasColumn('users', 'login_code')) {
                $table->string('login_code')->nullable();
            }

            if (Schema::hasColumn('users', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('users', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};











