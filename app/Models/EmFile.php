<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmFile extends Model
{
    protected $table = 'em_files';

    protected $fillable = [
        'machine_code', 'year', 'month', 'source_filename', 'imported_by',
    ];

    public function personnel()
    {
        return $this->hasMany(EmPersonnel::class, 'em_file_id');
    }

    public function surface()
    {
        return $this->hasMany(EmSurface::class, 'em_file_id');
    }
}
