<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speaker_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speaker_id')->constrained()->onDelete('cascade');
            $table->string('alias');
            $table->timestamps();

            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speaker_aliases');
    }
};
