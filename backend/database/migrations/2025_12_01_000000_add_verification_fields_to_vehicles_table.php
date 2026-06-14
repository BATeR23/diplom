<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('ownership_document_path')->nullable()->after('plate_number');
            $table->string('license_document_path')->nullable()->after('ownership_document_path');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('license_document_path');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null')->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'ownership_document_path',
                'license_document_path',
                'verification_status',
                'verified_by',
                'verified_at',
                'verification_notes',
            ]);
        });
    }
};

