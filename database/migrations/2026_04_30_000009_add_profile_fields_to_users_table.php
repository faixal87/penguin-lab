<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('last_user_agent');
            $table->string('phone_no')->nullable()->after('profile_photo');
            $table->string('program')->nullable()->after('phone_no');
            $table->string('semester')->nullable()->after('program');
            $table->string('class_name')->nullable()->after('semester');
            $table->string('registration_no')->nullable()->after('class_name');
            $table->string('staff_no')->nullable()->after('registration_no');
            $table->string('department')->nullable()->after('staff_no');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'phone_no',
                'program',
                'semester',
                'class_name',
                'registration_no',
                'staff_no',
                'department',
            ]);
        });
    }
};
