<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_hour_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_hour_id')->constrained('office_hours')->cascadeOnDelete();
            $table->string('student_name');
            $table->string('student_email');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['office_hour_id', 'student_email']);
            $table->index('student_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hour_signups');
    }
};
