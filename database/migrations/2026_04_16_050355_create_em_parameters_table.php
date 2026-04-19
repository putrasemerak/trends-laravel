<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('em_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('machine_code', 20);
            // Which sheet the block came from: 'sheet1' or 'sp_as'
            $table->string('sheet_source', 10)->default('sheet1');
            // e.g. 'fdab', 'garment', 'wall', 'floor', 'machine',
            //      'tank', 'nozzle', 'settle_plate', 'active_sampling'
            $table->string('test_type', 30);
            // Human label from Excel (e.g. 'F/dab', 'Wall', 'Tank 5')
            $table->string('param_label', 50)->nullable();
            // Room/area label (e.g. 'Filling Room', 'Packing Room')
            $table->string('room_label', 80)->nullable();
            $table->boolean('is_personnel')->default(false); // F/dab, Garment
            $table->decimal('std_limit', 8, 2)->nullable();
            $table->decimal('action_limit', 8, 2)->nullable();
            $table->decimal('alert_limit', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['machine_code', 'sheet_source', 'test_type', 'room_label'], 'em_param_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('em_parameters');
    }
};
