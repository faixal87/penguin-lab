<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linux_username')->nullable()->after('department');
            $table->string('linux_password')->nullable()->after('linux_username');
            $table->string('container_name')->nullable()->after('linux_password');
            $table->boolean('terminal_enabled')->default(false)->after('container_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'linux_username',
                'linux_password',
                'container_name',
                'terminal_enabled',
            ]);
        });
    }
};
