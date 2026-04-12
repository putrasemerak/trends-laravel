<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bioburden extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'TS_0010';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'prodline',
        'batch',
        'prodname',
        'datetested',
        'runno',
        'tamcr1',
        'tamcr2',
        'tymcr1',
        'tymcr2',
        'resultavg',
        'limit',
        'AddDate',
        'AddTime',
        'AddUser',
        'Status',
    ];

    protected $casts = [
        'datetested' => 'date',
        'tamcr1' => 'integer',
        'tamcr2' => 'integer',
        'tymcr1' => 'integer',
        'tymcr2' => 'integer',
        'resultavg' => 'float',
    ];

    /**
     * Scope to active records only.
     */
    public function scopeActive($query)
    {
        return $query->where('Status', 'ACTIVE');
    }

    /**
     * Scope to a specific production line.
     */
    public function scopeForProdline($query, string $prodline)
    {
        return $query->where('prodline', $prodline);
    }

    /**
     * Scope to a date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('datetested', [$from, $to]);
    }
}
