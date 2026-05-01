<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('scenarios', 'hint_2')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->text('hint_2')->nullable()->after('hint');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('scenarios', 'hint_2')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('hint_2');
        });
    }
};
