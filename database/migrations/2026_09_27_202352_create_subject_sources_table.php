<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The evidence behind a subject's full details. Mirrors `sources` (including its 2048-character
     * URL columns) without is_primary, plus a position for the admin's order.
     */
    public function up(): void
    {
        Schema::create('subject_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('title')->nullable();
            $table->string('source_type')->nullable();
            $table->string('archived_url', 2048)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_sources');
    }
};
