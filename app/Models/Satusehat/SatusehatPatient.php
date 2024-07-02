<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SatusehatPatient extends Model
{
    use HasFactory;
    protected $connection = 'mysql_satusehat';
    protected $table = 'patients';
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

    public static function cek($MedicalNo, $SSN)
    {
        $pasien_lokal = SatusehatPatient::where('MedicalNo', $MedicalNo)->first();

        $pasien_IHS = null;

        if ($pasien_lokal) {
            $pasien_NIK = $pasien_lokal->NIK;
            $pasien_IHS = $pasien_lokal->IHS;
            if ($SSN != null && $pasien_lokal->NIK != $SSN) {
                $pasien_update = $pasien_lokal;
                $pasien_update->NIK = $SSN;
                $pasien_update->save();

                $pasien_NIK = $pasien_update->NIK;
            }
        } else {
            $pasien_store = new SatusehatPatient();
            $pasien_store->MedicalNo = $MedicalNo;
            $pasien_store->NIK = $SSN;
            $pasien_store->IHS = null;
            $pasien_store->save();

            $pasien_NIK = $pasien_store->NIK;
        }

        return [
            'MedicalNo' => $MedicalNo,
            'NIK' => $pasien_NIK,
            'IHS' => $pasien_IHS,
        ];
    }

    public static function saveIHS($MedicalNo, $SSN, $ihs)
    {
        try {
            $pasien_lokal = SatusehatPatient::where('MedicalNo', $MedicalNo)->first();
            $pasien_lokal->IHS = $ihs;
            $pasien_lokal->save();
            return $pasien_lokal;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
