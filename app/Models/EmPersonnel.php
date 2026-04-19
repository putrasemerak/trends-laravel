<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmPersonnel extends Model
{
    protected $table = 'em_personnel';

    protected $fillable = [
        'em_file_id', 'test_type', 'sample_date', 'emp_no',
        'result', 'is_na', 'std_limit', 'action_limit', 'alert_limit', 'anomaly',
    ];

    protected $casts = [
        'sample_date' => 'date',
        'is_na'       => 'boolean',
        'anomaly'     => 'boolean',
    ];

    public function file()
    {
        return $this->belongsTo(EmFile::class, 'em_file_id');
    }
}
