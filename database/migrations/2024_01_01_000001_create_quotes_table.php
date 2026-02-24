<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->foreignId('speaker_id')->nullable()->constrained()->onDelete('set null');
            $table->string('slug')->unique();
            $table->text('context')->nullable();
            $table->string('location')->nullable();
            $table->date('occurred_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->enum('status', ['published', 'draft', 'pending'])->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('view_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_featured');
            $table->index('user_id');
            $table->index('occurred_at');
            $table->index('published_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
