<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'linux_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('linux_password');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'linux_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('linux_password')->nullable()->after('linux_username');
            });
        }
    }
};
