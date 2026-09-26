<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Match the 2048-character limit that QuoteRequest validates. At varchar(255),
     * long links (archive.org snapshots especially) failed on insert in Postgres.
     */
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->string('url', 2048)->change();
            $table->string('archived_url', 2048)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->string('url')->change();
            $table->string('archived_url')->nullable()->change();
        });
    }
};
