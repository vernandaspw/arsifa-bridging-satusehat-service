<?php

namespace App\Models\RsRajal;

use App\Models\Satusehat\SatusehatPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalPasien extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'rs_m_pasien';
    protected $guarded = ['MedicalNo'];
    protected $primaryKey = 'MedicalNo';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    

}
