<?php

namespace App\Models\Satusehat;

use App\Models\Sphaira\SphairaParamedic;
use App\Services\SatuSehat\PracticionerService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SatusehatPractitioner extends Model
{
    use HasFactory;
    protected $connection = 'mysql_satusehat';
    protected $table = 'practitioners';
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

    public static function cek($id, $code, $nik)
    {
        $practitioner = SatusehatPractitioner::query();
        if ($id) {
            $practitioner->where('ParamedicID', $id);
        }
        if ($code) {
            $practitioner->where('ParamedicCode', $code);
        }
        if ($nik) {
            $practitioner->where('ParamedicNIK', $nik);
        }
        $data = $practitioner->first();
        if ($data) {
            $ID = $data->ParamedicID;
            $NIK = $data->ParamedicNIK;
            $IHS = $data->IHS;
            if ($nik != null && $data->ParamedicNIK != $nik) {
                $data_update = $data;
                $data_update->ParamedicNIK = $nik;
                $data_update->save();
            }
        } else {
            $data_store = new SatusehatPractitioner();
            $data_store->ParamedicID = $id;
            $data_store->ParamedicCode = $code;
            $data_store->ParamedicNIK = $nik;
            $data_store->IHS = null;
            $data_store->save();

            $ID = $data_store->ParamedicID;
            $NIK = $data_store->ParamedicNIK;
            $IHS = $data_store->IHS;
        }
        return [
            'ID' => $ID,
            'NIK' => $NIK,
            'IHS' => $IHS,
        ];
    }

    public static function getAllNewSphaira()
    {
        try {
            $sphairaCode = SphairaParamedic::pluck('paramedicCode')->toArray();
            $localCode = SatusehatPractitioner::pluck('ParamedicCode')->toArray();
            $diffCodes = array_diff($sphairaCode, $localCode);
            $datas = [];
            if ($diffCodes) {
                foreach ($diffCodes as $code) {
                    $data = SphairaParamedic::where('ParamedicCode', $code)->select('ParamedicID', 'ParamedicCode', 'TaxRegistrantNo')->first();
                    $datas[] = SatusehatPractitioner::create([
                        'ParamedicID' => $data->ParamedicID,
                        'ParamedicCode' => $data->ParamedicCode,
                        'ParamedicNIK' => $data->TaxRegistrantNo,
                    ]);
                }
            }

            return response()->json([
                'msg' => 'success',
                'data' => $datas,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public static function getAllNikSphaira()
    {
        try {
            $sphairas = SphairaParamedic::pluck('paramedicCode', 'TaxRegistrantNo')->toArray();

            foreach ($sphairas as $sphairaNIK => $code) {
                $data = SatusehatPractitioner::where('ParamedicCode', $code)->first();
                $data->update([
                    'ParamedicNIK' => $sphairaNIK,
                ]);
            }

            return response()->json([
                'msg' => 'success',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public static function getAllIHS()
    {
        try {
            set_time_limit(500);
            $locals = SatusehatPractitioner::where('ParamedicNIK', '!=', null)->where('ParamedicNIK', '!=', '')->where('ParamedicNIK', '!=', '-')->where('IHS', null)->get();

            // dd($locals->toArray());
            foreach ($locals as $local) {
                if (strlen($local->ParamedicNIK) == 16) {
                    $ss = PracticionerService::getByNIK($local->ParamedicNIK);
                    if ($ss != null) {
                        $ihs = $ss['entry'][0]['resource']['id'];
                        $local->update([
                            'IHS' => $ihs,
                        ]);
                    }
                }
            }

            return response()->json([
                'msg' => 'success',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => $e->getMessage(),
            ], 500);
        }
    }
}
