<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('em_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('em_file_id')->constrained('em_files')->cascadeOnDelete();
            // 'fdab' or 'garment'
            $table->string('test_type', 20);
            $table->date('sample_date');
            // varchar — supports both numeric (22058) and alpha (C2406)
            $table->string('emp_no', 15);
            $table->decimal('result', 8, 2)->nullable();
            $table->boolean('is_na')->default(false);
            $table->decimal('std_limit', 8, 2)->nullable();
            $table->decimal('action_limit', 8, 2)->nullable();
            $table->decimal('alert_limit', 8, 2)->nullable();
            // anomaly: result > alert_limit (or action_limit if no alert)
            $table->boolean('anomaly')->default(false);
            $table->timestamps();

            $table->index(['em_file_id', 'test_type', 'sample_date'], 'em_pers_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('em_personnel');
    }
};
