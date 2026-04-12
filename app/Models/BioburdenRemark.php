<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioburdenRemark extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'TS_0011';
    public $timestamps = false;

    protected $fillable = [
        'remark',
        'monthyear',
        'prodline',
        'AddDate',
        'AddUser',
        'EditDate',
        'EditUser',
    ];
}
