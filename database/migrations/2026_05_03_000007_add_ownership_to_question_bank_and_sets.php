<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('question_bank', 'visibility')) {
                $table->string('visibility')->default('shared')->after('created_by');
            }
        });

        Schema::table('question_sets', function (Blueprint $table) {
            if (! Schema::hasColumn('question_sets', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('question_sets', 'visibility')) {
                $table->string('visibility')->default('shared')->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            if (Schema::hasColumn('question_sets', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('question_sets', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });

        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('question_bank', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
