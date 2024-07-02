<?php

namespace App\Http\Controllers;

use App\Models\Satusehat\SatusehatPatient;
use App\Models\Sphaira\SphairaPatient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function get(Request $request)
    {

    }
    public function getAllNewSphaira(Request $request)
    {
        // set_time_limit(500);
        // try {
        //     $sphairas = SphairaPatient::select('MedicalNo','SSN')->get();

        //     $datas = [];
        //     foreach ($sphairas as $sphaira) {
        //         $item['MedicalNo'] = $sphaira->MedicalNo;
        //         $item['SSN'] = $sphaira->SSN;
        //         $locals = SatusehatPatient::where('MedicalNo', $sphaira->MedicalNo)->where('NIK', $sphaira->SSN)->first();
        //         if(!isset($locals)){
        //             $datas[] = $item;
        //         }
        //         // dd($datas);
        //     }
        //     dd($datas);
        //     // dd($sphairaCode);
        //     // dd($localCode);
        //     $diffs = array_diff($sphairaCode, $localCode);
        //     // dd($diffs);
        //     if ($diffs) {
        //         $sphairaPatients = SphairaPatient::whereIn('MedicalNo', $diffs)->get();
        //         dd($sphairaPatients);
        //         $insertData = [];
        //         foreach ($sphairaPatients as $sp) {
        //             $insertData[] = [
        //                 'MedicalNo' => $sp->MedicalNo,
        //                 'NIK' => $sp->SSN,
        //             ];
        //         }
        //         SatusehatPatient::insert($insertData);
        //         return response()->json([
        //             'msg' => 'success',
        //             'data' => $insertData,
        //         ]);
        //     }
        //     return response()->json([
        //         'msg' => 'success',
        //         'data' => 'tidak ada data terbaru',
        //     ]);

        // } catch (\Throwable $e) {
        //     return response()->json([
        //         'msg' => $e->getMessage(),
        //     ], 500);
        // }
    }
    public function getAllNikSphaira(Request $request)
    {

    }
    public function getAllIHS(Request $request)
    {

    }
    public function createByNIK(Request $request)
    {

    }
}
