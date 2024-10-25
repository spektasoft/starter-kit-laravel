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
        Schema::table(app(config('curator.model'))->getTable(), function (Blueprint $table) {
            $table->ulid('id')->change();
            $table->foreignUlid('creator_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = app(config('curator.model'))->getTable();
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            DB::statement("ALTER TABLE $tableName MODIFY id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT");
            $table->dropConstrainedForeignId('creator_id');
        });
    }
};
