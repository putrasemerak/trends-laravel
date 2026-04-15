<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old unique key and recreate with prodname included
        // so different products sharing the same batch/run/date are distinct
        DB::connection('devdb')->statement(
            'ALTER TABLE TS_0020 DROP CONSTRAINT UQ_TS0020_key'
        );
        DB::connection('devdb')->statement(
            'ALTER TABLE TS_0020 ADD CONSTRAINT UQ_TS0020_key
             UNIQUE (prodline, batch, prodname, filing, runno, datetested)'
        );
    }

    public function down(): void
    {
        DB::connection('devdb')->statement(
            'ALTER TABLE TS_0020 DROP CONSTRAINT UQ_TS0020_key'
        );
        DB::connection('devdb')->statement(
            'ALTER TABLE TS_0020 ADD CONSTRAINT UQ_TS0020_key
             UNIQUE (prodline, batch, filing, runno, datetested)'
        );
    }
};
