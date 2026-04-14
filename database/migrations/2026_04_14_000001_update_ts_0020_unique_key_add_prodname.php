<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'devdb';

    public function up(): void
    {
        // 1. Drop the current unique constraint
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            DROP CONSTRAINT UQ_TS0020_key
        ");

        // 2. Add new constraint that includes prodname
        DB::connection('devdb')->statement("
            ALTER TABLE TS_0020
            ADD CONSTRAINT UQ_TS0020_key
                UNIQUE (prodline, batch, filing, prodname, runno, datetested)
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
            ADD CONSTRAINT UQ_TS0020_key
                UNIQUE (prodline, batch, filing, runno, datetested)
        ");
    }
};
