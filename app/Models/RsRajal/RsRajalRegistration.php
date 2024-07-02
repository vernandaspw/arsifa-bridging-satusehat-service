<?php

namespace App\Models\RsRajal;

use App\Models\Sphaira\SphairaRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsRajalRegistration extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_rs_rajal';
    protected $table = 'rs_registration';
    protected $guarded = ['reg_no'];
    protected $primaryKey = 'reg_no';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    public function pasien()
    {
        return $this->belongsTo(RsRajalPasien::class, 'reg_medrec', 'MedicalNo');
    }

    public static function cekIHSdariSphaira($noreg)
    {
        $sphaira = SphairaRegistration::select('EncounterIHS', 'EncounterIHSsanbox')->where('RegistrationNo', $noreg)->first();
        if (!$sphaira) {
            return null;
        }
        // production
        // $encounter_id = $sphaira->EncounterIHS;

        // sanbox
        $encounter_id = $sphaira->EncounterIHSsanbox;

        if (!$encounter_id) {
            return null;
        }
        return $encounter_id;
    }
}
