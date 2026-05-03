<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'container_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('container_status')->nullable()->after('container_name');
            });
        }

        if (! Schema::hasColumn('users', 'terminal_last_started_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('terminal_last_started_at')->nullable()->after('container_status');
            });
        }

        if (! Schema::hasColumn('users', 'terminal_last_stopped_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('terminal_last_stopped_at')->nullable()->after('terminal_last_started_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'terminal_last_stopped_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('terminal_last_stopped_at');
            });
        }

        if (Schema::hasColumn('users', 'terminal_last_started_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('terminal_last_started_at');
            });
        }

        if (Schema::hasColumn('users', 'container_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('container_status');
            });
        }
    }
};
