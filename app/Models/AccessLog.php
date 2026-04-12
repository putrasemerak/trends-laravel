<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $table = 'sy_0103';
    public $timestamps = true;

    protected $fillable = ['empno', 'title'];
}
