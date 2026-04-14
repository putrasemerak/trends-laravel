<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'devdb';

    public function up(): void
    {
        // 1. Add filing column (default '-' so existing rows are NOT NULL)
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            ADD filing VARCHAR(10) NOT NULL DEFAULT '-'
        ");

        // 2. Drop the old unique constraint
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            DROP CONSTRAINT UQ_TS0020_prodline_batch_runno_date
        ");

        // 3. Add new unique constraint that includes filing
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            ADD CONSTRAINT UQ_TS0020_key
                UNIQUE (prodline, batch, filing, runno, datetested)
        ");
    }

    public function down(): void
    {
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            DROP CONSTRAINT UQ_TS0020_key
        ");

        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            ADD CONSTRAINT UQ_TS0020_prodline_batch_runno_date
                UNIQUE (prodline, batch, runno, datetested)
        ");

        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            DROP COLUMN filing
        ");
    }
};
