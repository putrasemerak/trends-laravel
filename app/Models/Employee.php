<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property string $EmpNo
 * @property string $Pass
 * @property-read EmployeeDetail|null $details
 */
class Employee extends Authenticatable
{
    protected $connection = 'sqlsrv';
    protected $table = 'SY_0050';
    protected $primaryKey = 'EmpNo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $hidden = ['Pass'];

    /**
     * Get the password for the user (used by Laravel Auth).
     */
    public function getAuthPassword(): string
    {
        return $this->Pass;
    }

    /**
     * Get employee details from SY_0100.
     */
    public function details()
    {
        return $this->hasOne(EmployeeDetail::class, 'empno', 'EmpNo');
    }
}
