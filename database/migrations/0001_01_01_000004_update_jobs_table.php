<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->ulid('id')->change();
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->ulid('id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            DB::statement('ALTER TABLE jobs MODIFY id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            DB::statement('ALTER TABLE failed_jobs MODIFY id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT');
        });
    }
};
