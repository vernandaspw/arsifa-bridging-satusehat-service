<?php

namespace App\Models\RsRajal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalPasienProsedur extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'rs_pasien_prosedur';
    protected $guarded = ['pprosedur_id'];
    protected $primaryKey = 'pprosedur_id';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
