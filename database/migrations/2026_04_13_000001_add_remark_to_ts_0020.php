<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'devdb';

    public function up(): void
    {
        DB::connection('devdb')->statement("
            IF NOT EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = 'TS_0020' AND COLUMN_NAME = 'remark'
            )
            BEGIN
                ALTER TABLE TS_0020 ADD remark VARCHAR(200) NULL
            END
        ");
    }

    public function down(): void
    {
        DB::connection('devdb')->statement("
            IF EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = 'TS_0020' AND COLUMN_NAME = 'remark'
            )
            BEGIN
                ALTER TABLE TS_0020 DROP COLUMN remark
            END
        ");
    }
};
