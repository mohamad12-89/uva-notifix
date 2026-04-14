<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('assigned_to_name')->nullable()->after('ta_selected');
            $table->string('assigned_to_email')->nullable()->after('assigned_to_name');
            $table->string('assigned_to_role', 20)->nullable()->after('assigned_to_email');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['assigned_to_name', 'assigned_to_email', 'assigned_to_role']);
        });
    }
};

