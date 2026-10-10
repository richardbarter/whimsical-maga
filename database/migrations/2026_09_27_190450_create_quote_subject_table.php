<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * position is the admin's order of subjects on the quote page. No timestamps, like quote_tag.
     */
    public function up(): void
    {
        Schema::create('quote_subject', function (Blueprint $table) {
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['quote_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_subject');
    }
};
