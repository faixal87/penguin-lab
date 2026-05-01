<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');
            $table->string('matric_no')->nullable()->unique()->after('role');
            $table->string('status')->default('approved')->after('matric_no');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->text('last_user_agent')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matric_no']);
            $table->dropColumn([
                'role',
                'matric_no',
                'status',
                'last_login_at',
                'last_login_ip',
                'last_user_agent',
            ]);
        });
    }
};
