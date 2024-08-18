<?php

namespace App\Models\RsRajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalIcd10 extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'icd10_bpjs';
    protected $guarded = ['NM_ICD10'];
    protected $primaryKey = 'NM_ICD10';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
