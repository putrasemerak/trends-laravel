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
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_NAME = 'TS_0020'
            )
            BEGIN
                CREATE TABLE TS_0020 (
                    id             INT IDENTITY(1,1) PRIMARY KEY,
                    prodline       VARCHAR(20)   NOT NULL,
                    batch          VARCHAR(50)   NOT NULL,
                    prodname       VARCHAR(100)  NULL,
                    datetested     DATE          NOT NULL,
                    runno          VARCHAR(10)   NULL DEFAULT 'R1',

                    -- Bioburden Result Raw Data (BBRaw) — col F-I from Excel
                    tamcr1         INT           NULL DEFAULT 0,
                    tamcr2         INT           NULL DEFAULT 0,
                    tymcr1         INT           NULL DEFAULT 0,
                    tymcr2         INT           NULL DEFAULT 0,

                    -- Bioburden Result (BBR) — col J-M from Excel
                    bbr_tamc_r1    INT           NULL DEFAULT 0,
                    bbr_tamc_r2    INT           NULL DEFAULT 0,
                    bbr_tymc_r1    INT           NULL DEFAULT 0,
                    bbr_tymc_r2    INT           NULL DEFAULT 0,

                    resultavg      FLOAT         NULL DEFAULT 0,
                    [limit]        FLOAT         NULL DEFAULT 10,

                    AddDate        DATE          NULL,
                    AddTime        TIME          NULL,
                    AddUser        VARCHAR(50)   NULL,
                    Status         VARCHAR(10)   NULL DEFAULT 'ACTIVE',

                    CONSTRAINT UQ_TS0020_prodline_batch_runno_date
                        UNIQUE (prodline, batch, runno, datetested)
                )
            END
        ");
    }

    public function down(): void
    {
        DB::connection('devdb')->statement("
            IF EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_NAME = 'TS_0020'
            )
            BEGIN
                DROP TABLE TS_0020
            END
        ");
    }
};
