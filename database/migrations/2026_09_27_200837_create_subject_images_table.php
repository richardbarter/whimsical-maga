<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Images uploaded from the full details editor. The subject's HTML refers to them by URL;
     * this table records the file and its details (alt text is required for accessibility).
     */
    public function up(): void
    {
        Schema::create('subject_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('alt_text');
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('dimensions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_images');
    }
};
