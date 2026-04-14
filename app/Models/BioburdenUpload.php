<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioburdenUpload extends Model
{
    protected $connection = 'devdb';
    protected $table = 'TS_0020';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'prodline',
        'batch',
        'filing',
        'prodname',
        'datetested',
        'runno',
        'tamcr1',
        'tamcr2',
        'tymcr1',
        'tymcr2',
        'bbr_tamc_r1',
        'bbr_tamc_r2',
        'bbr_tymc_r1',
        'bbr_tymc_r2',
        'resultavg',
        'limit',
        'remark',
        'AddDate',
        'AddTime',
        'AddUser',
        'Status',
    ];

    protected $casts = [
        'datetested'  => 'date',
        'tamcr1'      => 'integer',
        'tamcr2'      => 'integer',
        'tymcr1'      => 'integer',
        'tymcr2'      => 'integer',
        'bbr_tamc_r1' => 'integer',
        'bbr_tamc_r2' => 'integer',
        'bbr_tymc_r1' => 'integer',
        'bbr_tymc_r2' => 'integer',
        'resultavg'   => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('Status', 'ACTIVE');
    }

    public function scopeForProdline($query, string $prodline)
    {
        return $query->where('prodline', $prodline);
    }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('datetested', [$from, $to]);
    }
}
