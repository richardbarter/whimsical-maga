<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename pivot to Laravel's alphabetical convention (category_quote)
     * and add updated_at so ->withTimestamps() works.
     */
    public function up(): void
    {
        Schema::rename('quote_category', 'category_quote');

        Schema::table('category_quote', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_quote', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });

        Schema::rename('category_quote', 'quote_category');
    }
};
