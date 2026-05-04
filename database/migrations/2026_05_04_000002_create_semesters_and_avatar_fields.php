<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->after('lecturer_id')->constrained('semesters')->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'default_avatar')) {
                $table->string('default_avatar')->nullable()->after('profile_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'default_avatar')) {
                $table->dropColumn('default_avatar');
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'semester_id')) {
                $table->dropConstrainedForeignId('semester_id');
            }
        });

        Schema::dropIfExists('semesters');
    }
};
