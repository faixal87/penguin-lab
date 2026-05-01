<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('scenarios', 'hint')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->text('hint')->nullable()->after('expected_command');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('scenarios', 'hint')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('hint');
        });
    }
};
