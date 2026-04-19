<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmSurface extends Model
{
    protected $table = 'em_surface';

    protected $fillable = [
        'em_file_id', 'sheet_source', 'test_type', 'location_label', 'room_label',
        'sample_date', 'result', 'is_na', 'std_limit', 'action_limit', 'alert_limit', 'anomaly',
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
