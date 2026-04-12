<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sy_0103', function (Blueprint $table) {
            $table->id();
            $table->string('empno', 20);
            $table->string('title', 500)->nullable();
            $table->timestamps();

            $table->index('empno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sy_0103');
    }
};
