<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('quote_type')->nullable()->after('status');
            $table->string('quote_type_note')->nullable()->after('quote_type');

            $table->index('quote_type');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['quote_type']);
            $table->dropColumn(['quote_type', 'quote_type_note']);
        });
    }
};
