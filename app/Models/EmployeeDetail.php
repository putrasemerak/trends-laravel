<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDetail extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SY_0100';
    protected $primaryKey = 'empno';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * Get the display name: "empno@prefername"
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->empno . '@' . $this->prefername;
    }
}
