<?php

namespace App\Models\RsRajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalParamedic extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'rs_m_paramedic';
    protected $guarded = ['MedicalNo'];
    protected $primaryKey = 'MedicalNo';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
