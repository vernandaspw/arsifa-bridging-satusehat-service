<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PractitionerController;
use App\Http\Controllers\Rajal\RajalBundleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth.jwt'])->group(function () {

    Route::get('organization/tree', [OrganizationController::class, 'getTree']);
    Route::get('organization/detail', [OrganizationController::class, 'detail']);
    Route::post('organization/create', [OrganizationController::class, 'create']);
    Route::put('organization/update', [OrganizationController::class, 'update']);

    Route::get('location/tree', [LocationController::class, 'getTree']);
    Route::get('location/ihs', [LocationController::class, 'tes']);
    Route::get('location/detail', [LocationController::class, 'detail']);
    Route::post('location/create', [LocationController::class, 'create']);
    Route::put('location/update', [LocationController::class, 'detail']);

    Route::get('practitioner', [PractitionerController::class, 'get']);
    Route::get('practitioner/get-all-new/sphaira', [PractitionerController::class, 'getAllNewSphaira']);
    Route::get('practitioner/get-all-nik/sphaira', [PractitionerController::class, 'getAllNikSphaira']);
    Route::get('practitioner/get-all-ihs/satusehat', [PractitionerController::class, 'getAllIHS']);

    Route::get('patient', [PatientController::class, 'get']);
    // Route::get('patient/get-all-new/sphaira', [PatientController::class, 'getAllNewSphaira']);
    // Route::get('patient/get-all-nik/sphaira', [PatientController::class, 'getAllNikSphaira']);
    // Route::get('patient/get-all-ihs/satusehat', [PatientController::class, 'getAllIHS']);
    // Route::post('patient/create-by-nik/satusehat', [PatientController::class, 'createByNIK']);

    // Route::get('patient/get-by-nik', [PatientController::class, 'getByNik']);
    // Route::get('patient/create-by-nik', [PatientController::class, 'createByNik']);
    // Route::get('patient/get-by-nik-mother', [PatientController::class, 'getByNikMother']);
    // Route::get('patient/create-by-nik-mother', [PatientController::class, 'createByNikMother']);

    Route::get('rajal/encounter', [RajalBundleController::class, 'encounter']);
    Route::get('rajal/get-reg/tanggal/{tanggal}', [RajalBundleController::class, 'getRegTgl']);
    Route::get('rajal/get-ihs-patient/tanggal/{tanggal}', [RajalBundleController::class, 'getIhsPasienTgl']);
    Route::get('rajal/get-ihs-location/tanggal', [RajalBundleController::class, 'getIhsLocationTgl']);
    // Route::get('rajal/get-ihs-practitioner/tanggal', [RajalBundleController::class, 'getIhsPractitionerTgl']);
    // Route::get('rajal/get-encounter-id/tanggal', [RajalBundleController::class, 'getEncounterIdTgl']);


});

Route::get('rajal/encounter/chart', [RajalBundleController::class, 'chart']);
