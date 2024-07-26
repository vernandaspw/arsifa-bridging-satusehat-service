<?php

namespace App\Http\Controllers\Rajal;

use App\Http\Controllers\Controller;
use App\Models\RsRajal\RsRajalRegistration;
use App\Models\Satusehat\SatusehatLocation;
use App\Models\Satusehat\SatusehatPatient;
use App\Models\Satusehat\SatusehatPractitioner;
use App\Models\Satusehat\SatusehatRegEncounter;
use App\Services\SatuSehat\PatientService;
use App\Services\SatuSehat\RajalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RajalBundleController extends Controller
{
    public function chart(Request $req)
    {
        try {
            $tipe = $req->input('tipe');
            $bulan = $req->input('bulan');
            $tahun = $req->input('tahun');

            if ($tahun == null || $bulan == null) {
                return response()->json(['msg' => 'tahun dan bulan wajib di isi'], 400);
            }
            // dd($tahun);

            if ($tipe != 'rajal' && $tipe != 'ranap' && $tipe != 'igd' && $tipe != null) {
                return response()->json(['msg' => 'tipe tidak valid'], 400);
            }

            $qdatas = SatusehatRegEncounter::query();
            $qdatas->where('isDeleted', 0);
            $qdatas->whereYear('reg_tgl', $tahun);
            $qdatas->whereMonth('reg_tgl', $bulan);
            if ($tipe) {
                $qdatas->where('tipe', $tipe);
            }

            $qdatas->selectRaw("DATE(reg_tgl) as date, COUNT(*) as count")->groupBy('date')->orderBy('date');
            $datas = $qdatas->get();

            $allDatas = [];
            foreach ($datas as $data) {
                $dailyDs = SatusehatRegEncounter::query()
                    ->where('isDeleted', 0)
                    ->whereDate('reg_tgl', $data->date)
                    ->orderBy('reg_tgl');
                if ($tipe) {
                    $dailyDs->where('tipe', $tipe);
                }
                $dailyDatas = $dailyDs->get();

                $encounter_id = 0;
                $encounter_id_null = 0;
                $location_ihs = 0;
                $location_ihs_null = 0;
                $patient_nik = 0;
                $patient_nik_null = 0;
                $patient_nik_not_16 = 0;
                $patient_ihs = 0;
                $patient_ihs_null = 0;
                $practitioner_nik = 0;
                $practitioner_nik_null = 0;
                $practitioner_nik_not_16 = 0;
                $practitioner_ihs = 0;
                $practitioner_ihs_null = 0;
                foreach ($dailyDatas as $dailyData) {
                    // ecnounter null
                    if ($dailyData->encounter_id != null && $dailyData->encounter_id != '') {
                        $encounter_id += 1;
                    }
                    if ($dailyData->encounter_id == null && $dailyData->encounter_id == '') {
                        $encounter_id_null += 1;
                    }

                    // location null
                    if ($dailyData->location_ihs != null && $dailyData->location_ihs != '') {
                        $location_ihs += 1;
                    }
                    if ($dailyData->location_ihs == null && $dailyData->location_ihs == '') {
                        $location_ihs_null += 1;
                    }

                    // // patient nik null/jika tidak 16 salah
                    if ($dailyData->patient_nik != null && $dailyData->patient_nik != '' && strlen($dailyData->patient_nik) == 16) {
                        $patient_nik += 1;
                    }
                    if ($dailyData->patient_nik == null && $dailyData->patient_nik == '') {
                        $patient_nik_null += 1;
                    }
                    if ($dailyData->patient_nik != null && $dailyData->patient_nik != '' && strlen($dailyData->patient_nik) != 16) {
                        $patient_nik_not_16 += 1;
                    }

                    // // patient ihs null ->tidak terdaftar
                    if ($dailyData->patient_ihs != null && $dailyData->patient_ihs != '') {
                        $patient_ihs += 1;
                    }
                    if ($dailyData->patient_ihs == null && $dailyData->patient_ihs == '') {
                        $patient_ihs_null += 1;
                    }

                    // // practitioner nik null/jika tidak 16 salah
                    if ($dailyData->practitioner_nik != null && $dailyData->practitioner_nik != '' && strlen($dailyData->practitioner_nik) == 16) {
                        $practitioner_nik += 1;
                    }
                    if ($dailyData->practitioner_nik == null && $dailyData->practitioner_nik == '') {
                        $practitioner_nik_null += 1;
                    }
                    if ($dailyData->practitioner_nik != null && $dailyData->practitioner_nik != '' && strlen($dailyData->practitioner_nik) != 16) {
                        $practitioner_nik_not_16 += 1;
                    }

                    // // practitioner ihs null -> tidak terdaftar
                    if ($dailyData->practitioner_ihs != null && $dailyData->practitioner_ihs != '') {
                        $practitioner_ihs += 1;
                    }
                    if ($dailyData->practitioner_ihs == null && $dailyData->practitioner_ihs == '') {
                        $practitioner_ihs_null += 1;
                    }
                }

                $allDatas[] = [
                    'date' => $data->date,
                    'total' => $data->count,
                    'encounter_id' => $encounter_id,
                    'encounter_id_null' => $encounter_id_null,
                    'location_ihs' => $location_ihs,
                    'location_ihs_null' => $location_ihs_null,
                    'patient_nik' => $patient_nik,
                    'patient_nik_null' => $patient_nik_null,
                    'patient_nik_not_16' => $patient_nik_not_16,
                    'patient_ihs' => $patient_ihs,
                    'patient_ihs_null' => $patient_ihs_null,
                    'practitioner_nik' => $practitioner_nik,
                    'practitioner_nik_null' => $practitioner_nik_null,
                    'practitioner_nik_not_16' => $practitioner_nik_not_16,
                    'practitioner_ihs' => $practitioner_ihs,
                    'practitioner_ihs_null' => $practitioner_ihs_null,
                ];
            }

            $reg_totalq = SatusehatRegEncounter::where('isDeleted', 0)->whereYear('reg_tgl', $tahun)->whereMonth('reg_tgl', $bulan);
            if ($tipe) {
                $reg_totalq->where('tipe', $tipe);
            }
            $reg_total = $reg_totalq->count();

            // disini
            $totalRegq = SatusehatRegEncounter::where('isDeleted', 0)->whereYear('reg_tgl', $tahun)->whereMonth('reg_tgl', $bulan);
            if ($tipe) {
                $totalRegq->where('tipe', $tipe);
            }
            $totalRegs = $totalRegq->get();
            $bulan_encounter_id = 0;
            $bulan_encounter_id_null = 0;
            $bulan_location_ihs = 0;
            $bulan_location_ihs_null = 0;
            $bulan_patient_nik = 0;
            $bulan_patient_nik_null = 0;
            $bulan_patient_nik_not_16 = 0;
            $bulan_patient_ihs = 0;
            $bulan_patient_ihs_null = 0;
            $bulan_practitioner_nik = 0;
            $bulan_practitioner_nik_null = 0;
            $bulan_practitioner_nik_not_16 = 0;
            $bulan_practitioner_ihs = 0;
            $bulan_practitioner_ihs_null = 0;
            foreach ($totalRegs as $totalReg) {
                // ecnounter null
                if ($totalReg->encounter_id != null && $totalReg->encounter_id != '') {
                    $bulan_encounter_id += 1;
                }
                if ($totalReg->encounter_id == null && $totalReg->encounter_id == '') {
                    $bulan_encounter_id_null += 1;
                }

                // location null
                if ($totalReg->location_ihs != null && $totalReg->location_ihs != '') {
                    $bulan_location_ihs += 1;
                }
                if ($totalReg->location_ihs == null && $totalReg->location_ihs == '') {
                    $bulan_location_ihs_null += 1;
                }

                // // patient nik null/jika tidak 16 salah
                if ($totalReg->patient_nik != null && $totalReg->patient_nik != '' && strlen($totalReg->patient_nik) == 16) {
                    $bulan_patient_nik += 1;
                }
                if ($totalReg->patient_nik == null && $totalReg->patient_nik == '') {
                    $bulan_patient_nik_null += 1;
                }
                if ($totalReg->patient_nik != null && $totalReg->patient_nik != '' && strlen($totalReg->patient_nik) != 16) {
                    $bulan_patient_nik_not_16 += 1;
                }

                // // patient ihs null ->tidak terdaftar
                if ($totalReg->patient_ihs != null && $totalReg->patient_ihs != '') {
                    $bulan_patient_ihs += 1;
                }
                if ($totalReg->patient_ihs == null && $totalReg->patient_ihs == '') {
                    $bulan_patient_ihs_null += 1;
                }

                // // practitioner nik null/jika tidak 16 salah
                if ($totalReg->practitioner_nik != null && $totalReg->practitioner_nik != '' && strlen($totalReg->practitioner_nik) == 16) {
                    $bulan_practitioner_nik += 1;
                }
                if ($totalReg->practitioner_nik == null && $totalReg->practitioner_nik == '') {
                    $bulan_practitioner_nik_null += 1;
                }
                if ($totalReg->practitioner_nik != null && $totalReg->practitioner_nik != '' && strlen($totalReg->practitioner_nik) != 16) {
                    $bulan_practitioner_nik_not_16 += 1;
                }

                // // practitioner ihs null -> tidak terdaftar
                if ($totalReg->practitioner_ihs != null && $totalReg->practitioner_ihs != '') {
                    $bulan_practitioner_ihs += 1;
                }
                if ($totalReg->practitioner_ihs == null && $totalReg->practitioner_ihs == '') {
                    $bulan_practitioner_ihs_null += 1;
                }
            }

            return response()->json([
                'msg' => 'success',
                'data' => [
                    'tipe' => $tipe ? $tipe : 'semua',
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'total_reg' => $reg_total,
                    'encounter_id' => $bulan_encounter_id,
                    'encounter_id_null' => $bulan_encounter_id_null,

                    'location_ihs' => $bulan_location_ihs,
                    'location_ihs_null' => $bulan_location_ihs_null,
                    'patient_nik' => $bulan_patient_nik,
                    'patient_nik_null' => $bulan_patient_nik_null,
                    'patient_nik_not_16' => $bulan_patient_nik_not_16,
                    'patient_ihs' => $bulan_patient_ihs,
                    'patient_ihs_null' => $bulan_patient_ihs_null,
                    'practitioner_nik' => $bulan_practitioner_nik,
                    'practitioner_nik_null' => $bulan_practitioner_nik_null,
                    'practitioner_nik_not_16' => $bulan_practitioner_nik_not_16,
                    'practitioner_ihs' => $bulan_practitioner_ihs,
                    'practitioner_ihs_null' => $bulan_practitioner_ihs_null,

                    'datas' => $allDatas,
                ],
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
    public function encounter(Request $req)
    {
        try {
            $tanggal = $req->input('tanggal');
            if (!$tanggal) {
                return response()->json(['msg' => 'tanggal wajib di isi'], 400);
            }

            $page = $req->input('page');
            $limit = $req->input('limit');
            $page = intval($page);
            $limit = intval($limit);

            if (!$page) {
                $page = 1;
            }
            if (!$limit) {
                $limit = 10;
            }

            $offset = ($page - 1) * $limit;

            $qdatas = SatusehatRegEncounter::query();
            $qdatas->where('isDeleted', 0);
            if ($tanggal) {
                $qdatas->whereDate('reg_tgl', $tanggal);
            }

            $qdatas->skip($offset)->take($limit);
            $datas = $qdatas->get();
            $total = $qdatas->count();

            return response()->json([
                'msg' => 'success',
                'total' => $total,
                'limit' => $limit,
                'page' => $page,
                'totalPages' => ceil($total / $limit),
                'datas' => $datas,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
    public static function getRegTgl($tanggal)
    {
        try {
            // RS RAJAL HANYA INPUT ROOM CODE

            if (!isset($tanggal)) {
                return response()->json([
                    'msg' => 'tanggal wajib di isi',
                ], 400);
            }

            // $page = $req->input('page');
            // $limit = $req->input('limit');
            // if (!$page) {
            //     $page = 1;
            // }
            // if (!$limit) {
            //     $limit = 10;
            // }
            // $offset = ($page - 1) * $limit;
            // $reg->skip($offset)->take($limit);

            $reg = RsRajalRegistration::whereDate('reg_tgl', $tanggal);
            $reg->where(DB::raw('SUBSTRING(reg_no, 6, 2)'), '=', 'RJ');
            $reg->select('reg_no', 'reg_medrec', 'reg_dokter', 'reg_tgl', 'reg_discharge_tanggal', 'reg_poli', 'reg_deleted');
            // $reg->take(2)->orderBy('reg_tgl', 'asc');
            $regs = $reg->get();
            // dd($regs);
            $storeRegs = [];
            $updateRegsDisharge = [];
            $updateRegsNoDisharge = [];
            foreach ($regs as $item) {

                // cek encounter pada reg sphaira, jika ada ambil encunter_id
                $encounter_id = RsRajalRegistration::cekIHSdariSphaira($item->reg_no);
                $locationIHS = SatusehatLocation::cek(null, null, $item->reg_poli, null);
                // dd($locationIHS);
                $pasien = SatusehatPatient::cek($item->reg_medrec, $item->pasien->SSN);
                $practitioner = SatusehatPractitioner::cek(null, $item->reg_dokter, null);

                $regsArray = [
                    'tipe' => 'rajal',
                    'encounter_id' => $encounter_id,
                    'noreg' => $item->reg_no,
                    'reg_tgl' => $item->reg_tgl,
                    'discharge_tgl' => $item->reg_discharge_tanggal,

                    'service_unit_id' => null,
                    'room_id' => null,
                    'room_code' => $item->reg_poli,
                    'bed_id' => null,
                    'location_ihs' => $locationIHS,

                    'MedicalNo' => $pasien['MedicalNo'],
                    'patient_nik' => $pasien['NIK'],
                    'patient_ihs' => $pasien['IHS'],

                    'practitioner_id' => $practitioner['ID'],
                    'practitioner_code' => $item->reg_dokter,
                    'practitioner_nik' => $practitioner['NIK'],
                    'practitioner_ihs' => $practitioner['IHS'],
                    'isDeleted' => $item->reg_deleted,
                ];
                // dd($regsArray);
                $reg_encounter = SatusehatRegEncounter::where('noreg', $regsArray['noreg'])->first();
                // jika tidak ada pada lokal encounter maka create encounter
                if (!isset($reg_encounter)) {
                    $storeRegs[] = $regsArray;
                } else {
                    // perbarui yg tidak memiliki encounter sja
                    $updateRegsNoDisharge[] = $regsArray;
                    // if ($regsArray['encounter_id'] == null) {
                    //     if ($regsArray['discharge_tgl'] == null) {
                    //     }
                    //     // else {
                    //     //     $updateRegsDisharge[] = $regsArray;
                    //     // }
                    // }
                }

            }
            // dd($storeRegs, $updateRegsNoDisharge, $updateRegsDisharge);

            // jika belum memiliki reg_encounter maka insert data
            $dataStoreRegs = array_chunk($storeRegs, 50);
            $storeDatas = [];
            foreach ($dataStoreRegs as $items) {
                foreach ($items as $item) {
                    $storeDatas[] = SatusehatRegEncounter::create($item);
                }
            }
            // return response()->json($updateRegsNoDisharge);
            // jika belum discharge, perbarui : discharge_tgl, location, practitioner, isDeleted,
            $dataUpdateRegsNoDisharge = array_chunk($updateRegsNoDisharge, 50);
            $dataNoDischarges = [];
            foreach ($dataUpdateRegsNoDisharge as $items) {
                foreach ($items as $item) {
                    $dataUpdate = SatusehatRegEncounter::where('noreg', $item['noreg'])->first();
                    // dd($dataUpdate->toArray(), $item);
                    // jika ada perbedaan data pada tiap item
                    // if($dataUpdate->encounter_id == null){
                    //     $dataUpdate->discharge_tgl = $item['discharge_tgl'];
                    // }
                    if (
                        ($dataUpdate->encounter_id == null && $dataUpdate->encounter_id != $item['encounter_id'])  ||
                        $dataUpdate->discharge_tgl != $item['discharge_tgl'] ||
                        $dataUpdate->service_unit_id != $item['service_unit_id'] ||
                        $dataUpdate->room_id != $item['room_id'] ||
                        $dataUpdate->room_code != $item['room_code'] ||
                        $dataUpdate->bed_id != $item['bed_id'] ||
                        $dataUpdate->location_ihs != $item['location_ihs'] ||
                        $dataUpdate->patient_nik != $item['patient_nik'] ||
                        $dataUpdate->patient_ihs != $item['patient_ihs'] ||
                        $dataUpdate->practitioner_id != $item['practitioner_id'] ||
                        $dataUpdate->practitioner_code != $item['practitioner_code'] ||
                        $dataUpdate->practitioner_nik != $item['practitioner_nik'] ||
                        $dataUpdate->practitioner_ihs != $item['practitioner_ihs'] ||
                        $dataUpdate->isDeleted != $item['isDeleted']
                    ) {
                        $dataUpdate->encounter_id = $item['encounter_id'];
                        $dataUpdate->discharge_tgl = $item['discharge_tgl'];
                        $dataUpdate->service_unit_id = $item['service_unit_id'];
                        $dataUpdate->room_id = $item['room_id'];
                        $dataUpdate->room_code = $item['room_code'];
                        $dataUpdate->bed_id = $item['bed_id'];
                        $dataUpdate->location_ihs = $item['location_ihs'];
                        $dataUpdate->patient_nik = $item['patient_nik'];
                        $dataUpdate->patient_ihs = $item['patient_ihs'];
                        $dataUpdate->practitioner_id = $item['practitioner_id'];
                        $dataUpdate->practitioner_code = $item['practitioner_code'];
                        $dataUpdate->practitioner_nik = $item['practitioner_nik'];
                        $dataUpdate->practitioner_ihs = $item['practitioner_ihs'];
                        $dataUpdate->isDeleted = $item['isDeleted'];
                        $dataUpdate->save();
                        $dataNoDischarges[] = $dataUpdate;
                    }

                }
            }

            // jika sudah discharge, perbarui : isDeleted,
            // $dataUpdateRegsDisharge = array_chunk($updateRegsDisharge, 50);
            // $dataDischarges = [];
            // foreach ($dataUpdateRegsDisharge as $items) {
            //     foreach ($items as $item) {
            //         $dataUpdate = SatusehatRegEncounter::where('noreg', $item['noreg'])->first();
            //         // $dataUpdate->isDelete
            //     }
            // }

            return response()->json([
                'msg' => 'success sync data',
                'data' => [
                    'data_store_count' => $storeDatas ? count($storeDatas) : 0,
                    'data_store' => $storeDatas ? $storeDatas : null,
                    'data_update_no_discharge_count' => $dataNoDischarges ? count($dataNoDischarges) : 0,
                    'data_update_no_discharge' => $dataNoDischarges ? $dataNoDischarges : null,
                    // 'data_update_discharge_count' => $dataDischarges ? count($dataDischarges) : 0,
                    // 'data_update_discharge' => $dataDischarges ? $dataDischarges : null,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public static function getIhsLocationTgl($tanggal)
    {
        if (!isset($tanggal)) {
            return response()->json([
                'msg' => 'tanggal wajib di isi',
            ], 400);
        }

        try {
            $qdata = SatusehatRegEncounter::whereDate('reg_tgl', $tanggal);
            // $qdata->where('encounter_id', null)->orWhere('encounter_id', '');
            $qdata->where('location_ihs', null)->orWhere('location_ihs', '');
            $qdata->where('isDeleted', 0);
            $datas = $qdata->get();

            // update location ihs
            // pada encounter dan tabel location
            $dataUpdated = [];
            foreach ($datas as $data) {
                $originalData = clone $data;

                // Proses pembaruan
                $location_ihs = SatusehatLocation::cek($data->service_unit_id, $data->room_id, $data->room_code, $data->bed_id);
                $data->location_ihs = $location_ihs;
                $data->save();

                // Bandingkan data asli dengan data baru
                if ($originalData != $data) {
                    $dataUpdated[] = $data;
                }
            }
            return response()->json([
                'msg' => 'success sync data',
                'data' => [
                    'data_updated_count' => $dataUpdated ? count($dataUpdated) : 0,
                    'data_updated' => $dataUpdated ? $dataUpdated : null,
                ],
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
    public static function getIhsPasienTgl($tanggal)
    {
        if (!isset($tanggal)) {
            return response()->json([
                'msg' => 'tanggal wajib di isi',
            ], 400);
        }

        try {

            $qdata = SatusehatRegEncounter::whereDate('reg_tgl', $tanggal);
            // $qdata->where('encounter_id', null)->orWhere('encounter_id', '');
            $qdata->where('patient_ihs', null)->orWhere('patient_ihs', '');
            $qdata->where('isDeleted', 0);
            $datas = $qdata->get();

            // update location ihs
            // pada encounter dan tabel location
            $chunks = array_chunk($datas->toArray(), 10);

            $dataUpdated = [];
            foreach ($chunks as $chunk) {
                foreach ($chunk as $data) {
                    // $ihs = PatientService::getByNIK('0003580888577');
                    $ihs = PatientService::getByNIK($data['patient_nik']);
                    //  dd($ihs);
                    if ($ihs) {
                        // Proses pembaruan
                        $savePatient = SatusehatPatient::saveIHS($data['MedicalNo'], $data['patient_nik'], $ihs);
                        if ($savePatient) {
                            $udata = SatusehatRegEncounter::where('noreg', $data['noreg'])->first();
                            $udata->patient_ihs = $ihs;
                            $udata->save();

                            $dataUpdated[] = $udata;
                        }

                    }
                }
            }
            return response()->json([
                'msg' => 'success sync ihs pasien',
                'data' => [
                    'data_updated_count' => $dataUpdated ? count($dataUpdated) : 0,
                    'data_updated' => $dataUpdated ? $dataUpdated : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
    public function getIhsPractitionerTgl(Request $req)
    {
        $tanggal = $req->input('tanggal');
        if (!isset($tanggal)) {
            return response()->json([
                'msg' => 'tanggal wajib di isi',
            ], 400);
        }

    }

    public function getEncounterIdTgl(Request $req)
    {
        $tanggal = $req->input('tanggal');
        if (!isset($tanggal)) {
            return response()->json([
                'msg' => 'tanggal wajib di isi',
            ], 400);
        }

    }

    public static function encounterPostPerTanggal($tanggal)
    {
        $tipe = 'rajal';
        // $tanggal = $req->tanggal;
        if (!isset($tanggal) && !isset($tanggal)) {
            return response()->json([
                'msg' => 'tipe & tanggal wajib di isi',
            ], 400);
        }

        try {
            // jika encounter_id tersedia, update data, jika tidak maka generate encounter_id
            $regs = SatusehatRegEncounter::where('tipe', $tipe)
            ->whereDate('reg_tgl', $tanggal)
            ->where('isDeleted', 0)
            ->where('patient_ihs', '!=', null)
            ->where('location_ihs', '!=', null)
            ->where('practitioner_ihs', '!=', null)
            ->get();
            $countPost = 0;
            $countUpdate = 0;
            // dd($regs);
            foreach ($regs as $reg) {
                // dd($reg->encounter_id);
                // encounter_id
                // location_ihs
                // patient_ihs
                // practitioner_ihs
                dd($reg);

                // $body = [
                //     'noreg' => $reg->noreg,
                //     'patient_id' =>
                //     'patient_name' => ,

                //     'practitionerIhs' => $registration['ihs_dokter'],
                //     'practitionerName' => $registration['nama_dokter'],
                //     'organizationId' => $organization_id,
                //     'locationId' => $location_id,
                //     'locationName' => $location_name,
                //     'statusHistory' => 'arrived',
                //     'RegistrationDateTime' => $registration['RegistrationDateTime'],
                //     'DischargeDateTime' => $registration['DischargeDateTime'],
                //     'diagnosas' => $registration['diagnosas'],
                // ];

                if ($reg->encounter_id == null) {
                    // post baru
                    // post bundle encounter
                        $body = ['aa' => 'aa'];
                        $encounterKunjunganBaru = RajalService::encounterKunjunganBaru($body);
                        if($encounterKunjunganBaru) {
                            $encounter_id = $encounterKunjunganBaru;
                            // jika success, kirim data langkah berikutnya
                            $encounterMasukRuang = RajalService::encounterMasukRuang($body, $encounter_id);
                            if($encounterMasukRuang) {
                                
                                return $encounterMasukRuang;
                            }
                            return $encounterKunjunganBaru;
                        }else{
                            return 'error';
                        }
                        // dd($resultApi);
                        // if (!empty($resultApi['entry'][0]['response']['resourceID'])) {
                        //     $encounterID = $resultApi['entry'][0]['response']['resourceID'];
                        // } else {
                        //     $url = $resultApi['entry'][0]['response']['location'];
                        //     $uuid = explode('/', parse_url($url, PHP_URL_PATH))[4];
                        //     $encounterID = $uuid;
                        // }

                        // if (empty($encounterID)) {
                        //     $errorMessage = 'EncounterID tidak valid';
                        //     return $this->emit('error', $errorMessage);
                        // }

                        // RegistrationService::updateEncounterId($noReg, $encounterID);
                        // $this->fetchData($this->tanggal);

                }else{
                    // perbaui, nanti
                    // cek data apa saja yg telah dikirim

                    //
                }
            }
            // return response()->json($regs);


            return response()->json([
                'msg' => 'success',
                'count_post' => $countPost,
                'count_update' => $countUpdate,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'data' => $e->getMessage()
            ], 500);
        }
    }

}
