<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('em_files', function (Blueprint $table) {
            $table->id();
            $table->string('machine_code', 20);   // e.g. BFS1, SVP1, MEDIBAG
            $table->smallInteger('year');
            $table->tinyInteger('month');          // 1–12
            $table->string('source_filename', 100)->nullable();
            $table->string('imported_by', 20)->nullable(); // emp_no of uploader
            $table->timestamps();

            $table->unique(['machine_code', 'year', 'month'], 'em_files_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('em_files');
    }
};
