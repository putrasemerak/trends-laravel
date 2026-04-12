<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramAccess extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SY_0055N';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * Get the program definition.
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'ProgID', 'ProgNo');
    }

    /**
     * Get the access level number (1-4).
     */
    public function getAccessLevelNumberAttribute(): int
    {
        return match ($this->ALevel) {
            '01 - Read Only' => 1,
            '02 - Read & Write' => 2,
            '03 - Read & Write & Edit' => 3,
            '04 - Full Access' => 4,
            default => 0,
        };
    }
}
