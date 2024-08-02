<?php

namespace App\Models\Satusehat;

use App\Models\RsRajal\RsRajalParamedic;
use App\Models\RsRajal\RsRajalPasien;
use App\Models\RsRajal\RsRajalPasienAsper;
use App\Models\RsRajal\RsRajalPasienDiagnosa;
use App\Models\RsRajal\RsRajalPasienProsedur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SatusehatRegEncounter extends Model
{
    use HasFactory;
    protected $connection = 'mysql_satusehat';
    protected $table = 'reg_encounters';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = Str::uuid()->toString();
        });
    }

    public function location()
    {
        return $this->belongsTo(SatusehatLocation::class, 'location_ihs', 'location_id');
    }

    public function rsrajal_patient()
    {
        return $this->belongsTo(RsRajalPasien::class, 'MedicalNo', 'MedicalNo');
    }

    public function rsrajal_practitioner()
    {
        return $this->belongsTo(RsRajalParamedic::class, 'practitioner_code', 'ParamedicCode');
    }

    public function rsrajal_asper()
    {
        return $this->belongsTo(RsRajalPasienAsper::class, 'noreg', 'asper_reg');
    }

    public function rsrajal_diagnosas()
    {
        return $this->hasMany(RsRajalPasienDiagnosa::class, 'pdiag_reg', 'noreg')->where('pdiag_deleted', 0);
    }

    public function rsrajal_prosedurs()
    {
        return $this->hasMany(RsRajalPasienProsedur::class, 'pprosedur_reg', 'noreg')->where('pprosedur_deleted', 0);
    }

}
