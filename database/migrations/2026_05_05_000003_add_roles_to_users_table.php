<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'roles')) {
                $table->json('roles')->nullable()->after('role');
            }
        });

        if (Schema::hasColumn('users', 'roles')) {
            DB::table('users')
                ->whereNull('roles')
                ->orderBy('id')
                ->get(['id', 'role'])
                ->each(function ($user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['roles' => json_encode([$user->role])]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'roles')) {
                $table->dropColumn('roles');
            }
        });
    }
};
