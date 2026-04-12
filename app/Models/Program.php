<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'SY_0061';
    protected $primaryKey = 'ProgNo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
