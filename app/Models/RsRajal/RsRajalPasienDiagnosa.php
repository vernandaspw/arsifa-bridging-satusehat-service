<?php

namespace App\Models\RsRajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalPasienDiagnosa extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'rs_pasien_diagnosa';
    protected $guarded = ['pdiag_id'];
    protected $primaryKey = 'pdiag_id';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
