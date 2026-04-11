<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->date('preferred_date')->nullable()->after('ta_selected');
            $table->string('preferred_time', 8)->nullable()->after('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['preferred_date', 'preferred_time']);
        });
    }
};
