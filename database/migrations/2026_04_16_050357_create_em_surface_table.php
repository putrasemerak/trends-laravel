<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Surface tests (Sheet1 wall/floor/machine/nozzle/tank)
        // AND air tests (SP & AS lain2 settle plate / active sampling)
        Schema::create('em_surface', function (Blueprint $table) {
            $table->id();
            $table->foreignId('em_file_id')->constrained('em_files')->cascadeOnDelete();
            // Source sheet: 'sheet1' or 'sp_as'
            $table->string('sheet_source', 10)->default('sheet1');
            // e.g. 'wall','floor','machine','tank','nozzle','settle_plate','active_sampling'
            $table->string('test_type', 30);
            // Column label in block header (e.g. 'Wall', 'Tank 5', 'Loc 1', 'F/Nozzle 2')
            $table->string('location_label', 50)->nullable();
            // Room label parsed from far-right annotation text
            $table->string('room_label', 80)->nullable();
            $table->date('sample_date');
            $table->decimal('result', 8, 2)->nullable();
            $table->boolean('is_na')->default(false);
            $table->decimal('std_limit', 8, 2)->nullable();
            $table->decimal('action_limit', 8, 2)->nullable();
            $table->decimal('alert_limit', 8, 2)->nullable();
            $table->boolean('anomaly')->default(false);
            $table->timestamps();

            $table->index(['em_file_id', 'test_type', 'sample_date'], 'em_surf_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('em_surface');
    }
};
