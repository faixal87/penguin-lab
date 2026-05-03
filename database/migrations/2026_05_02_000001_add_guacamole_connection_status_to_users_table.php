<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'guacamole_connection_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('guacamole_connection_status')->default('Not synced')->after('container_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'guacamole_connection_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('guacamole_connection_status');
            });
        }
    }
};
