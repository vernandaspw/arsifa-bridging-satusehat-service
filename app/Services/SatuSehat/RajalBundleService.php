<?php

namespace App\Services\SatuSehat;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class RajalBundleService
{

    public static function wibToUTC($waktuWIB)
    {
        // $dateTimeWIB = new DateTime($waktuWIB);
        // $dateTimeWIB->modify("-7 hours");
        // $waktuUTC = $dateTimeWIB->format('Y-m-d\TH:i:sP');

        $date = new DateTime($waktuWIB);
        $date->modify('-7 hours'); // Mengurangi 7 jam
        return $date->format('Y-m-d\TH:i:sP');
        // return $waktuUTC;
    }

    public static function rajalBundleBody(
        $encounter_id,
        $noreg,
        $reg_tgl,
        $discharge_tgl,

        $location_ihs,
        $location_nama,

        $patient_ihs,
        $patient_nama,

        $practitioner_ihs,
        $practitioner_nama,

        $org_id,

        $asper_nadi,
        $asper_created_at,

        $diagnosa_primary,
        $diagnosa_secondary,
        $diagnosas,
        $prosedurs
    ) {
        // dd($reg_tgl, RajalBundleService::wibToUTC($reg_tgl));
        $reg_tgl = RajalBundleService::wibToUTC($reg_tgl);
        $discharge_tgl = RajalBundleService::wibToUTC($discharge_tgl);
        // dd($diagnosas);
        $encounter_diagnosis = [];
        foreach ($diagnosas as $encounter_diagnosa) {
            $rank = 1;
            $encounter_diagnosis[] = [
                "condition" => [
                    "reference" => "urn:uuid:" . $encounter_diagnosa['uuid'],
                    "display" => $encounter_diagnosa['pdiag_name'],
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => "DD",
                            "display" => "Discharge diagnosis",
                        ],
                    ],
                ],
                "rank" => $encounter_diagnosa['pdiag_tipe'] == 'UTAMA' ? 1 : $rank++,
            ];
        }

        $encounter = [
            "fullUrl" => "urn:uuid:" . $encounter_id,
            "resource" => [
                "resourceType" => "Encounter",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/encounter/" . $org_id,
                        "value" => $noreg,
                    ],
                ],
                "status" => "finished",
                "statusHistory" => [
                    [
                        "status" => "arrived",
                        "period" => [
                            "start" => $reg_tgl,
                            "end" => $reg_tgl,
                        ],
                    ],
                    [
                        "status" => "in-progress",
                        "period" => [
                            "start" => $reg_tgl,
                            "end" => $reg_tgl,
                        ],
                    ],
                    [
                        "status" => "finished",
                        "period" => [
                            "start" => $discharge_tgl,
                            "end" => $discharge_tgl,
                        ],
                    ],
                ],
                "class" => [
                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code" => "AMB",
                    "display" => "ambulatory",
                ],
                "subject" => [
                    "reference" => "Patient/" . $patient_ihs,
                    "display" => $patient_nama,
                ],
                "participant" => [
                    [
                        "type" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                        "code" => "ATND",
                                        "display" => "attender",
                                    ],
                                ],
                            ],
                        ],
                        "individual" => [
                            "reference" => "Practitioner/" . $practitioner_ihs,
                            "display" => $practitioner_nama,
                        ],
                    ],
                ],
                "period" => [
                    "start" => $reg_tgl,
                    "end" => $reg_tgl,
                ],
                "diagnosis" => $encounter_diagnosis,
                "hospitalization" => [
                    "dischargeDisposition" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                "code" => "oth",
                                "display" => "other-hcf",
                            ],
                        ],
                        "text" => "",
                    ],
                ],
                "location" => [
                    [
                        "extension" => [
                            [
                                "extension" => [
                                    [
                                        "url" => "value",
                                        "valueCodeableConcept" => [
                                            "coding" => [
                                                [
                                                    "system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                    "code" => "reguler",
                                                    "display" => "Kelas Reguler",
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "url" => "upgradeClassIndicator",
                                        "valueCodeableConcept" => [
                                            "coding" => [
                                                [
                                                    "system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                    "code" => "kelas-tetap",
                                                    "display" => "Kelas Tetap Perawatan",
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                            ],
                        ],
                        "location" => [
                            "reference" => "Location/" . $location_ihs,
                            "display" => $location_nama,
                        ],
                        "period" => [
                            "start" => $reg_tgl,
                            "end" => $reg_tgl,
                        ],
                    ],
                ],
                "serviceProvider" => [
                    "reference" => "Organization/" . $org_id,
                ],
            ],
            "request" => [
                "method" => "POST",
                "url" => "Encounter",
            ],
        ];

        $obervation_nadi = [];
        if (!empty($asper_nadi)) {
            $observation_nadi = [
                "fullUrl" => "urn:uuid:" . Str::uuid(),
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "vital-signs",
                                    "display" => "Vital Signs",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8867-4",
                                "display" => "Heart rate",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/" . $patient_ihs,
                        "display" => $patient_nama,
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:" . $encounter_id,
                    ],
                    "effectiveDateTime" => $asper_created_at,
                    "issued" => $asper_created_at,
                    "performer" => [
                        [
                            "reference" => "Practitioner/" . $practitioner_ihs,
                            "display" => $practitioner_nama,
                        ],
                    ],
                    "valueQuantity" => [
                        "value" => $asper_nadi,
                        "unit" => "{beats}/min",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "{beats}/min",
                    ],
                ],
                "request" => [
                    "method" => "POST",
                    "url" => "Observation",
                ],
            ];
        }

        $conditions = [];
        // dd($diagnosas);
        foreach ($diagnosas as $diagnosa) {
            $conditions = [
                "fullUrl" => "urn:uuid:" . $diagnosa['uuid'],
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => $diagnosa['pdiag_diagnosa'],
                                "display" => $diagnosa['pdiag_name'],
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/" . $patient_ihs,
                        "display" => $patient_nama,
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:" . $encounter_id,
                    ],
                    "onsetDateTime" => RajalBundleService::wibToUTC($diagnosa['created_at']),
                    "recordedDate" => RajalBundleService::wibToUTC($diagnosa['created_at']),
                ],
                "request" => [
                    "method" => "POST",
                    "url" => "Condition",
                ],
            ];
        }

        $data = [];
        $data[] = $encounter;
        $data[] = $observation_nadi;
        $data[] = $conditions;

        // $data = [
        // $encounter,
        // [
        //     "fullUrl" => "urn:uuid:c566d6e2-4da0-4895-9bcb-8051dd16548c",
        //     "resource" => [
        //         "resourceType" => "Condition",
        //         "clinicalStatus" => [
        //             "coding" => [
        //                 [
        //                     "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
        //                     "code" => "active",
        //                     "display" => "Active",
        //                 ],
        //             ],
        //         ],
        //         "category" => [
        //             [
        //                 "coding" => [
        //                     [
        //                         "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
        //                         "code" => "problem-list-item",
        //                         "display" => "Problem List Item",
        //                     ],
        //                 ],
        //             ],
        //         ],
        //         "code" => [
        //             "coding" => [
        //                 [
        //                     "system" => "http://snomed.info/sct",
        //                     "code" => "16932000",
        //                     "display" => "Batuk darah",
        //                 ],
        //             ],
        //         ],
        //         "subject" => [
        //             "reference" => "Patient/{{Patient_ID}}",
        //             "display" => "{{Patient_Name}}",
        //         ],
        //         "encounter" => [
        //             "reference" => "urn:uuid:{{Encounter_id}}",
        //         ],
        //         "onsetDateTime" => "2023-02-02T00:00:00+00:00",
        //         "recordedDate" => "2023-08-31T01:00:00+00:00",
        //         "recorder" => [
        //             "reference" => "Practitioner/{{Practitioner_ID}}",
        //             "display" => "{{Practitioner_Name}}",
        //         ],
        //         "note" => [
        //             [
        //                 "text" => "Batuk Berdarah sejak 3bl yll",
        //             ],
        //         ],
        //     ],
        //     "request" => [
        //         "method" => "POST",
        //         "url" => "Condition",
        //     ],
        // ],

        // $observation_nadi,

        // {
        //     "fullUrl": "urn:uuid:{{Observation_Kesadaran}}",
        //     "resource": {
        //         "resourceType": "Observation",
        //         "status": "final",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/observation-category",
        //                         "code": "vital-signs",
        //                         "display": "Vital Signs"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://loinc.org",
        //                     "code": "67775-7",
        //                     "display": "Level of responsiveness"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "effectiveDateTime": "2023-08-31T01:10:00+00:00",
        //         "issued": "2023-08-31T01:10:00+00:00",
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/{{Practitioner_ID}}",
        //                 "display": "{{Practitioner_Name}}"
        //             }
        //         ],
        //         "valueCodeableConcept": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "248234008",
        //                     "display": "Mentally alert"
        //                 }
        //             ]
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Observation"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{CarePlan_RencanaRawat}}",
        //     "resource": {
        //         "resourceType": "CarePlan",
        //         "status": "active",
        //         "intent": "plan",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "736271009",
        //                         "display": "Outpatient care plan"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "title": "Rencana Rawat Pasien",
        //         "description": "Rencana Rawat Pasien",
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "created": "2023-08-31T01:20:00+00:00",
        //         "author": {
        //             "reference": "Practitioner/{{Practitioner_ID}}",
        //             "display": "{{Practitioner_Name}}"
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "CarePlan"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{CarePlan_Instruksi}}",
        //     "resource": {
        //         "resourceType": "CarePlan",
        //         "status": "active",
        //         "intent": "plan",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "736271009",
        //                         "display": "Outpatient care plan"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "title": "Instruksi Medik dan Keperawatan Pasien",
        //         "description": "Penanganan TB Pasien dilakukan dengan pemberian pengobatan TB.",
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "created": "2023-08-31T01:20:00+00:00",
        //         "author": {
        //             "reference": "Practitioner/{{Practitioner_ID}}"
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "CarePlan"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Procedure_PraRad}}",
        //     "resource": {
        //         "resourceType": "Procedure",
        //         "status": "not-done",
        //         "category": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "103693007",
        //                     "display": "Diagnostic procedure"
        //                 }
        //             ],
        //             "text": "Prosedur diagnostik"
        //         },
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "792805006",
        //                     "display": "Fasting"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "performedPeriod": {
        //             "start": "2023-07-04T09:30:00+00:00",
        //             "end": "2023-07-04T09:30:00+00:00"
        //         },
        //         "performer": [
        //             {
        //                 "actor": {
        //                     "reference": "Practitioner/{{Practitioner_ID}}",
        //                     "display": "{{Practitioner_Name}}"
        //                 }
        //             }
        //         ],
        //         "note": [
        //             {
        //                 "text": "Tidak puasa sebelum pemeriksaan radiologi"
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Procedure"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Observation_PraRad}}",
        //     "resource": {
        //         "resourceType": "Observation",
        //         "status": "final",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/observation-category",
        //                         "code": "survey",
        //                         "display": "Survey"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://loinc.org",
        //                     "code": "82810-3",
        //                     "display": "Pregnancy status"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Kunjungan {{Patient_Name}} 4 Juli 2023"
        //         },
        //         "effectiveDateTime": "2023-07-04T09:30:00+00:00",
        //         "issued": "2023-07-04T09:30:00+00:00",
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/{{Practitioner_ID}}"
        //             }
        //         ],
        //         "valueCodeableConcept": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "60001007",
        //                     "display": "Not pregnant"
        //                 }
        //             ]
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Observation"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{AllergyIntolerance_PraRad}}",
        //     "resource": {
        //         "resourceType": "AllergyIntolerance",
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/allergy/{{Org_ID}}",
        //                 "value": "P20240001"
        //             }
        //         ],
        //         "clinicalStatus": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
        //                     "code": "active",
        //                     "display": "Active"
        //                 }
        //             ]
        //         },
        //         "verificationStatus": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
        //                     "code": "confirmed",
        //                     "display": "Confirmed"
        //                 }
        //             ]
        //         },
        //         "category": [
        //             "medication"
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://sys-ids.kemkes.go.id/kfa",
        //                     "code": "91000928",
        //                     "display": "Barium Sulfate"
        //                 }
        //             ],
        //             "text": "Alergi Barium Sulfate"
        //         },
        //         "patient": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Kunjungan {{Patient_Name}} 4 Juli 2023"
        //         },
        //         "recordedDate": "2023-07-04T09:30:00+00:00",
        //         "recorder": {
        //             "reference": "Practitioner/{{Practitioner_ID}}"
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "AllergyIntolerance"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{ServiceRequest_Rad}}",
        //     "resource": {
        //         "resourceType": "ServiceRequest",
        //         "identifier": [
        //             {
        //                 "system": "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
        //                 "value": "{{Rad_SRID_CXR}}"
        //             },
        //             {
        //                 "use": "usual",
        //                 "type": {
        //                     "coding": [
        //                         {
        //                             "system": "http://terminology.hl7.org/CodeSystem/v2-0203",
        //                             "code": "ACSN"
        //                         }
        //                     ]
        //                 },
        //                 "system": "http://sys-ids.kemkes.go.id/acsn/{{Org_ID}}",
        //                 "value": "{{ACSN}}"
        //             }
        //         ],
        //         "status": "active",
        //         "intent": "original-order",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "363679005",
        //                         "display": "Imaging"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "priority": "routine",
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://loinc.org",
        //                     "code": "24648-8",
        //                     "display": "XR Chest PA upr"
        //                 }
        //             ],
        //             "text": "Pemeriksaan CXR PA"
        //         },
        //         "orderDetail": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://dicom.nema.org/resources/ontology/DCM",
        //                         "code": "DX"
        //                     }
        //                 ],
        //                 "text": "Modality Code: DX"
        //             },
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://sys-ids.kemkes.go.id/ae-title",
        //                         "display": "XR0001"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "occurrenceDateTime": "2023-08-31T02:05:00+00:00",
        //         "requester": {
        //             "reference": "Practitioner/{{Practitioner_ID}}",
        //             "display": "{{Practitioner_Name}}"
        //         },
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/10012572188",
        //                 "display": "Dokter Radiologist"
        //             }
        //         ],
        //         "reasonCode": [
        //             {
        //                 "text": "Permintaan pemeriksaan CXR PA untuk tuberculosis"
        //             }
        //         ],
        //         "supportingInfo": [
        //             {
        //                 "reference": "urn:uuid:{{Observation_PraRad}}"
        //             },
        //             {
        //                 "reference": "urn:uuid:{{Procedure_PraRad}}"
        //             },
        //             {
        //                 "reference": "urn:uuid:{{AllergyIntolerance_PraRad}}"
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "ServiceRequest"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Observation_Rad}}",
        //     "resource": {
        //         "resourceType": "Observation",
        //         "basedOn": [
        //             {
        //                 "reference": "urn:uuid:{{ServiceRequest_Rad}}"
        //             }
        //         ],
        //         "status": "final",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/observation-category",
        //                         "code": "imaging",
        //                         "display": "Imaging"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://loinc.org",
        //                     "code": "24648-8",
        //                     "display": "XR Chest PA upr"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "effectiveDateTime": "2023-08-31T02:35:00+00:00",
        //         "issued": "2023-08-31T02:35:00+00:00",
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/{{Practitioner_ID}}",
        //                 "display": "Dokter Radiologist"
        //             }
        //         ],
        //         "valueString": "Left upper and middle lung zones show reticulonodular opacities.\nThe left apical lung zone shows a cavitary lesion( active TB).\nLeft apical pleural thickening\nMild mediastinum widening is noted\nNormal heart size.\nFree costophrenic angles."
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Observation"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{DiagnosticReport_Rad}}",
        //     "resource": {
        //         "resourceType": "DiagnosticReport",
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/diagnostic/{{Org_ID}}/rad",
        //                 "value": "52343522"
        //             }
        //         ],
        //         "basedOn": [
        //             {
        //                 "reference": "urn:uuid:{{ServiceRequest_Rad}}"
        //             }
        //         ],
        //         "status": "final",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/v2-0074",
        //                         "code": "RAD",
        //                         "display": "Radiology"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://loinc.org",
        //                     "code": "24648-8",
        //                     "display": "XR Chest PA upr"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "effectiveDateTime": "2023-08-31T05:00:00+00:00",
        //         "issued": "2023-08-31T05:00:00+00:00",
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/{{Practitioner_ID}}"
        //             },
        //             {
        //                 "reference": "Organization/{{Org_ID}}"
        //             }
        //         ],
        //         "result": [
        //             {
        //                 "reference": "urn:uuid:{{Observation_Rad}}"
        //             }
        //         ],
        //         "imagingStudy": [
        //             {
        //                 "reference": "urn:uuid:354e1828-b094-493a-b393-2c18a28476ea"
        //             }
        //         ],
        //         "conclusion": "Active Tuberculosis indicated"
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "DiagnosticReport"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Procedure_Terapetik}}",
        //     "resource": {
        //         "resourceType": "Procedure",
        //         "status": "completed",
        //         "category": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "277132007",
        //                     "display": "Therapeutic procedure"
        //                 }
        //             ],
        //             "text": "Therapeutic procedure"
        //         },
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://hl7.org/fhir/sid/icd-9-cm",
        //                     "code": "93.94",
        //                     "display": "Respiratory medication administered by nebulizer"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Tindakan Nebulisasi {{Patient_Name}} pada Selasa tanggal 31 Agustus 2023"
        //         },
        //         "performedPeriod": {
        //             "start": "2023-08-31T02:27:00+00:00",
        //             "end": "2023-08-31T02:27:00+00:00"
        //         },
        //         "performer": [
        //             {
        //                 "actor": {
        //                     "reference": "Practitioner/{{Practitioner_ID}}",
        //                     "display": "{{Practitioner_Name}}"
        //                 }
        //             }
        //         ],
        //         "reasonCode": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://hl7.org/fhir/sid/icd-10",
        //                         "code": "A15.0",
        //                         "display": "Tuberculosis of lung, confirmed by sputum microscopy with or without culture"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "bodySite": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "74101002",
        //                         "display": "Both lungs"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "note": [
        //             {
        //                 "text": "Nebulisasi untuk melegakan sesak napas"
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Procedure"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Procedure_Konseling}}",
        //     "resource": {
        //         "resourceType": "Procedure",
        //         "status": "completed",
        //         "category": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "409063005",
        //                     "display": "Counselling"
        //                 }
        //             ],
        //             "text": "Counselling"
        //         },
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://hl7.org/fhir/sid/icd-9-cm",
        //                     "code": "94.4",
        //                     "display": "Other psychotherapy and counselling"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Konseling {{Patient_Name}} pada Selasa tanggal 31 Agustus 2023"
        //         },
        //         "performedPeriod": {
        //             "start": "2023-08-31T02:27:00+00:00",
        //             "end": "2023-08-31T02:27:00+00:00"
        //         },
        //         "performer": [
        //             {
        //                 "actor": {
        //                     "reference": "Practitioner/{{Practitioner_ID}}",
        //                     "display": "{{Practitioner_Name}}"
        //                 }
        //             }
        //         ],
        //         "reasonCode": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://hl7.org/fhir/sid/icd-10",
        //                         "code": "A15.0",
        //                         "display": "Tuberculosis of lung, confirmed by sputum microscopy with or without culture"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "note": [
        //             {
        //                 "text": "Konseling keresahan pasien karena diagnosis TB"
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Procedure"
        //     }
        // },
        // $conditions,
        // {
        //     "fullUrl": "urn:uuid:{{Procedure_Edukasi}}",
        //     "resource": {
        //         "resourceType": "Procedure",
        //         "status": "completed",
        //         "category": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "409073007",
        //                     "display": "Education"
        //                 }
        //             ]
        //         },
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "61310001",
        //                     "display": "Nutrition education"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "performedPeriod": {
        //             "start": "2023-08-31T03:30:00+00:00",
        //             "end": "2023-08-31T03:40:00+00:00"
        //         },
        //         "performer": [
        //             {
        //                 "actor": {
        //                     "reference": "Practitioner/{{Practitioner_ID}}",
        //                     "display": "{{Practitioner_Name}}"
        //                 }
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Procedure"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Medication_forRequest}}",
        //     "resource": {
        //         "resourceType": "Medication",
        //         "meta": {
        //             "profile": [
        //                 "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
        //             ]
        //         },
        //         "extension": [
        //             {
        //                 "url": "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
        //                 "valueCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://terminology.kemkes.go.id/CodeSystem/medication-type",
        //                             "code": "NC",
        //                             "display": "Non-compound"
        //                         }
        //                     ]
        //                 }
        //             }
        //         ],
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
        //                 "value": "123456789"
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://sys-ids.kemkes.go.id/kfa",
        //                     "code": "93001019",
        //                     "display": "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)"
        //                 }
        //             ]
        //         },
        //         "status": "active",
        //         "manufacturer": {
        //             "reference": "Organization/900001"
        //         },
        //         "form": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.kemkes.go.id/CodeSystem/medication-form",
        //                     "code": "BS023",
        //                     "display": "Kaplet Salut Selaput"
        //                 }
        //             ]
        //         },
        //         "ingredient": [
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000330",
        //                             "display": "Rifampin"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 150,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000328",
        //                             "display": "Isoniazid"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 75,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000329",
        //                             "display": "Pyrazinamide"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 400,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000288",
        //                             "display": "Ethambutol"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 275,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Medication"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{MedicationRequest_id}}",
        //     "resource": {
        //         "resourceType": "MedicationRequest",
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
        //                 "value": "123456788"
        //             },
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
        //                 "value": "123456788-1"
        //             }
        //         ],
        //         "status": "completed",
        //         "intent": "order",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
        //                         "code": "outpatient",
        //                         "display": "Outpatient"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "priority": "routine",
        //         "medicationReference": {
        //             "reference": "urn:uuid:{{Medication_forRequest}}",
        //             "display": "{{Medication_Name}}"
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "authoredOn": "2023-08-31T03:27:00+00:00",
        //         "requester": {
        //             "reference": "Practitioner/{{Practitioner_ID}}",
        //             "display": "{{Practitioner_Name}}"
        //         },
        //         "reasonReference": [
        //             {
        //                 "reference": "urn:uuid:{{Condition_DiagnosisPrimer}}",
        //                 "display": "{{DiagnosisPrimer_Text}}"
        //             }
        //         ],
        //         "courseOfTherapyType": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.hl7.org/CodeSystem/medicationrequest-course-of-therapy",
        //                     "code": "continuous",
        //                     "display": "Continuing long term therapy"
        //                 }
        //             ]
        //         },
        //         "dosageInstruction": [
        //             {
        //                 "sequence": 1,
        //                 "additionalInstruction": [
        //                     {
        //                         "coding": [
        //                             {
        //                                 "system": "http://snomed.info/sct",
        //                                 "code": "418577003",
        //                                 "display": "Take at regular intervals. Complete the prescribed course unless otherwise directed"
        //                             }
        //                         ]
        //                     }
        //                 ],
        //                 "patientInstruction": "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
        //                 "timing": {
        //                     "repeat": {
        //                         "frequency": 1,
        //                         "period": 1,
        //                         "periodUnit": "d"
        //                     }
        //                 },
        //                 "route": {
        //                     "coding": [
        //                         {
        //                             "system": "http://www.whocc.no/atc",
        //                             "code": "O",
        //                             "display": "Oral"
        //                         }
        //                     ]
        //                 },
        //                 "doseAndRate": [
        //                     {
        //                         "type": {
        //                             "coding": [
        //                                 {
        //                                     "system": "http://terminology.hl7.org/CodeSystem/dose-rate-type",
        //                                     "code": "ordered",
        //                                     "display": "Ordered"
        //                                 }
        //                             ]
        //                         },
        //                         "doseQuantity": {
        //                             "value": 4,
        //                             "unit": "TAB",
        //                             "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                             "code": "TAB"
        //                         }
        //                     }
        //                 ]
        //             }
        //         ],
        //         "dispenseRequest": {
        //             "dispenseInterval": {
        //                 "value": 1,
        //                 "unit": "days",
        //                 "system": "http://unitsofmeasure.org",
        //                 "code": "d"
        //             },
        //             "validityPeriod": {
        //                 "start": "2023-08-31T03:27:00+00:00",
        //                 "end": "2024-07-22T14:27:00+00:00"
        //             },
        //             "numberOfRepeatsAllowed": 0,
        //             "quantity": {
        //                 "value": 120,
        //                 "unit": "TAB",
        //                 "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                 "code": "TAB"
        //             },
        //             "expectedSupplyDuration": {
        //                 "value": 30,
        //                 "unit": "days",
        //                 "system": "http://unitsofmeasure.org",
        //                 "code": "d"
        //             },
        //             "performer": {
        //                 "reference": "Organization/{{Org_ID}}"
        //             }
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "MedicationRequest"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{QuestionnaireResponse_KajianResep}}",
        //     "resource": {
        //         "resourceType": "QuestionnaireResponse",
        //         "questionnaire": "https://fhir.kemkes.go.id/Questionnaire/Q0007",
        //         "status": "completed",
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "authored": "2023-08-31T03:00:00+00:00",
        //         "author": {
        //             "reference": "Practitioner/10009880728",
        //             "display": "Apoteker A"
        //         },
        //         "source": {
        //             "reference": "Patient/{{Patient_ID}}"
        //         },
        //         "item": [
        //             {
        //                 "linkId": "1",
        //                 "text": "Persyaratan Administrasi",
        //                 "item": [
        //                     {
        //                         "linkId": "1.1",
        //                         "text": "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
        //                         "answer": [
        //                             {
        //                                 "valueCoding": {
        //                                     "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                     "code": "OV000052",
        //                                     "display": "Sesuai"
        //                                 }
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "linkId": "1.2",
        //                         "text": "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
        //                         "answer": [
        //                             {
        //                                 "valueCoding": {
        //                                     "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                     "code": "OV000052",
        //                                     "display": "Sesuai"
        //                                 }
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "linkId": "1.3",
        //                         "text": "Apakah tanggal resep sudah sesuai?",
        //                         "answer": [
        //                             {
        //                                 "valueCoding": {
        //                                     "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                     "code": "OV000052",
        //                                     "display": "Sesuai"
        //                                 }
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "linkId": "1.4",
        //                         "text": "Apakah ruangan/unit asal resep sudah sesuai?",
        //                         "answer": [
        //                             {
        //                                 "valueCoding": {
        //                                     "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                     "code": "OV000052",
        //                                     "display": "Sesuai"
        //                                 }
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "linkId": "2",
        //                         "text": "Persyaratan Farmasetik",
        //                         "item": [
        //                             {
        //                                 "linkId": "2.1",
        //                                 "text": "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
        //                                 "answer": [
        //                                     {
        //                                         "valueCoding": {
        //                                             "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                             "code": "OV000052",
        //                                             "display": "Sesuai"
        //                                         }
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "2.2",
        //                                 "text": "Apakah dosis dan jumlah obat sudah sesuai?",
        //                                 "answer": [
        //                                     {
        //                                         "valueCoding": {
        //                                             "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                             "code": "OV000052",
        //                                             "display": "Sesuai"
        //                                         }
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "2.3",
        //                                 "text": "Apakah stabilitas obat sudah sesuai?",
        //                                 "answer": [
        //                                     {
        //                                         "valueCoding": {
        //                                             "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                             "code": "OV000052",
        //                                             "display": "Sesuai"
        //                                         }
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "2.4",
        //                                 "text": "Apakah aturan dan cara penggunaan obat sudah sesuai?",
        //                                 "answer": [
        //                                     {
        //                                         "valueCoding": {
        //                                             "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                             "code": "OV000052",
        //                                             "display": "Sesuai"
        //                                         }
        //                                     }
        //                                 ]
        //                             }
        //                         ]
        //                     },
        //                     {
        //                         "linkId": "3",
        //                         "text": "Persyaratan Klinis",
        //                         "item": [
        //                             {
        //                                 "linkId": "3.1",
        //                                 "text": "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
        //                                 "answer": [
        //                                     {
        //                                         "valueCoding": {
        //                                             "system": "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
        //                                             "code": "OV000052",
        //                                             "display": "Sesuai"
        //                                         }
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "3.2",
        //                                 "text": "Apakah terdapat duplikasi pengobatan?",
        //                                 "answer": [
        //                                     {
        //                                         "valueBoolean": false
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "3.3",
        //                                 "text": "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
        //                                 "answer": [
        //                                     {
        //                                         "valueBoolean": false
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "3.4",
        //                                 "text": "Apakah terdapat kontraindikasi pengobatan?",
        //                                 "answer": [
        //                                     {
        //                                         "valueBoolean": false
        //                                     }
        //                                 ]
        //                             },
        //                             {
        //                                 "linkId": "3.5",
        //                                 "text": "Apakah terdapat dampak interaksi obat?",
        //                                 "answer": [
        //                                     {
        //                                         "valueBoolean": false
        //                                     }
        //                                 ]
        //                             }
        //                         ]
        //                     }
        //                 ]
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "QuestionnaireResponse"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Medication_forDispense}}",
        //     "resource": {
        //         "resourceType": "Medication",
        //         "meta": {
        //             "profile": [
        //                 "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"
        //             ]
        //         },
        //         "extension": [
        //             {
        //                 "url": "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
        //                 "valueCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://terminology.kemkes.go.id/CodeSystem/medication-type",
        //                             "code": "NC",
        //                             "display": "Non-compound"
        //                         }
        //                     ]
        //                 }
        //             }
        //         ],
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
        //                 "value": "123456789"
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://sys-ids.kemkes.go.id/kfa",
        //                     "code": "93001019",
        //                     "display": "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)"
        //                 }
        //             ]
        //         },
        //         "status": "active",
        //         "manufacturer": {
        //             "reference": "Organization/900001"
        //         },
        //         "form": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.kemkes.go.id/CodeSystem/medication-form",
        //                     "code": "BS023",
        //                     "display": "Kaplet Salut Selaput"
        //                 }
        //             ]
        //         },
        //         "ingredient": [
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000330",
        //                             "display": "Rifampin"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 150,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000328",
        //                             "display": "Isoniazid"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 75,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000329",
        //                             "display": "Pyrazinamide"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 400,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://sys-ids.kemkes.go.id/kfa",
        //                             "code": "91000288",
        //                             "display": "Ethambutol"
        //                         }
        //                     ]
        //                 },
        //                 "isActive": true,
        //                 "strength": {
        //                     "numerator": {
        //                         "value": 275,
        //                         "system": "http://unitsofmeasure.org",
        //                         "code": "mg"
        //                     },
        //                     "denominator": {
        //                         "value": 1,
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code": "TAB"
        //                     }
        //                 }
        //             }
        //         ],
        //         "batch": {
        //             "lotNumber": "1625042A",
        //             "expirationDate": "2025-07-22T14:27:00+00:00"
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Medication"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{MedicationDispense_id}}",
        //     "resource": {
        //         "resourceType": "MedicationDispense",
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
        //                 "value": "123456788"
        //             },
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
        //                 "value": "123456788-1"
        //             }
        //         ],
        //         "status": "completed",
        //         "category": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
        //                     "code": "outpatient",
        //                     "display": "Outpatient"
        //                 }
        //             ]
        //         },
        //         "medicationReference": {
        //             "reference": "urn:uuid:{{Medication_forDispense}}",
        //             "display": "{{Medication_Name}}"
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "context": {
        //             "reference": "urn:uuid:{{Encounter_id}}"
        //         },
        //         "performer": [
        //             {
        //                 "actor": {
        //                     "reference": "Practitioner/{{Practitioner_ID}}",
        //                     "display": "Apoteker Miller"
        //                 }
        //             }
        //         ],
        //         "location": {
        //             "reference": "Location/{{Location_farmasi_id}}",
        //             "display": "Farmasi"
        //         },
        //         "authorizingPrescription": [
        //             {
        //                 "reference": "urn:uuid:{{MedicationRequest_id}}"
        //             }
        //         ],
        //         "quantity": {
        //             "value": 120,
        //             "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //             "code": "TAB"
        //         },
        //         "daysSupply": {
        //             "value": 30,
        //             "unit": "Day",
        //             "system": "http://unitsofmeasure.org",
        //             "code": "d"
        //         },
        //         "whenPrepared": "2023-08-31T03:27:00+00:00",
        //         "whenHandedOver": "2023-08-31T03:27:00+00:00",
        //         "dosageInstruction": [
        //             {
        //                 "sequence": 1,
        //                 "additionalInstruction": [
        //                     {
        //                         "coding": [
        //                             {
        //                                 "system": "http://snomed.info/sct",
        //                                 "code": "418577003",
        //                                 "display": "Take at regular intervals. Complete the prescribed course unless otherwise directed"
        //                             }
        //                         ]
        //                     }
        //                 ],
        //                 "patientInstruction": "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
        //                 "timing": {
        //                     "repeat": {
        //                         "frequency": 1,
        //                         "period": 1,
        //                         "periodUnit": "d"
        //                     }
        //                 },
        //                 "doseAndRate": [
        //                     {
        //                         "type": {
        //                             "coding": [
        //                                 {
        //                                     "system": "http://terminology.hl7.org/CodeSystem/dose-rate-type",
        //                                     "code": "ordered",
        //                                     "display": "Ordered"
        //                                 }
        //                             ]
        //                         },
        //                         "doseQuantity": {
        //                             "value": 4,
        //                             "unit": "TAB",
        //                             "system": "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                             "code": "TAB"
        //                         }
        //                     }
        //                 ]
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "MedicationDispense"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{ClinicalImpression_Prognosis}}",
        //     "resource": {
        //         "resourceType": "ClinicalImpression",
        //         "identifier": [
        //             {
        //                 "use": "official",
        //                 "system": "http://sys-ids.kemkes.go.id/clinicalimpression/{{Org_ID}}",
        //                 "value": "{{Prognosis_ID}}"
        //             }
        //         ],
        //         "status": "completed",
        //         "description": "{{Patient_Name}} terdiagnosa TB, dan adanya DM-2",
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Kunjungan {{Patient_Name}} di hari Selasa, 31 Agustus 2023"
        //         },
        //         "effectiveDateTime": "2023-10-31T03:37:31+00:00",
        //         "date": "2023-10-31T03:15:31+00:00",
        //         "assessor": {
        //             "reference": "Practitioner/{{Practitioner_ID}}"
        //         },
        //         "problem": [
        //             {
        //                 "reference": "urn:uuid:{{Condition_DiagnosisPrimer}}"
        //             }
        //         ],
        //         "investigation": [
        //             {
        //                 "code": {
        //                     "text": "Pemeriksaan CXR PA"
        //                 },
        //                 "item": [
        //                     {
        //                         "reference": "urn:uuid:{{DiagnosticReport_Rad}}"
        //                     },
        //                     {
        //                         "reference": "urn:uuid:{{Observation_Rad}}"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "summary": "Prognosis terhadap Tuberkulosis, disertai adanya riwayat Diabetes Mellitus tipe 2",
        //         "finding": [
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://hl7.org/fhir/sid/icd-10",
        //                             "code": "A15.0",
        //                             "display": "Tuberculosis of lung, confirmed by sputum microscopy with or without culture"
        //                         }
        //                     ]
        //                 },
        //                 "itemReference": {
        //                     "reference": "urn:uuid:{{Condition_DiagnosisPrimer}}"
        //                 }
        //             },
        //             {
        //                 "itemCodeableConcept": {
        //                     "coding": [
        //                         {
        //                             "system": "http://hl7.org/fhir/sid/icd-10",
        //                             "code": "E44.1",
        //                             "display": "Mild protein-calorie malnutrition"
        //                         }
        //                     ]
        //                 },
        //                 "itemReference": {
        //                     "reference": "urn:uuid:{{Condition_DiagnosisSekunder}}"
        //                 }
        //             }
        //         ],
        //         "prognosisCodeableConcept": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "170968001",
        //                         "display": "Prognosis good"
        //                     }
        //                 ]
        //             }
        //         ]
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "ClinicalImpression"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{ServiceRequest_Rujukan}}",
        //     "resource": {
        //         "resourceType": "ServiceRequest",
        //         "identifier": [
        //             {
        //                 "system": "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
        //                 "value": "000012345"
        //             }
        //         ],
        //         "status": "active",
        //         "intent": "original-order",
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://snomed.info/sct",
        //                         "code": "3457005",
        //                         "display": "Patient referral"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "priority": "routine",
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "737481003",
        //                     "display": "Inpatient care management"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Kunjungan {{Patient_Name}} di hari Kamis, 31 Agustus 2023 "
        //         },
        //         "occurrenceDateTime": "2023-08-31T04:25:00+00:00",
        //         "requester": {
        //             "reference": "Practitioner/{{Practitioner_ID}}",
        //             "display": "{{Practitioner_Name}}"
        //         },
        //         "performer": [
        //             {
        //                 "reference": "Practitioner/{{Practitioner_ID}}",
        //                 "display": "Fatma"
        //             }
        //         ],
        //         "locationCode": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
        //                         "code": "HOSP",
        //                         "display": "Hospital"
        //                     },
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
        //                         "code": "AMB",
        //                         "display": "Ambulance"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "reasonCode": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://hl7.org/fhir/sid/icd-10",
        //                         "code": "A15.0",
        //                         "display": "Tuberculosis of lung, confirmed by sputum microscopy with or without culture"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "patientInstruction": "Rujukan ke Rawat Inap RSUP Fatmawati. Dalam keadaan darurat dapat menghubungi hotline Fasyankes di nomor 14045"
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "ServiceRequest"
        //     }
        // },
        // {
        //     "fullUrl": "urn:uuid:{{Condition_Stabil}}",
        //     "resource": {
        //         "resourceType": "Condition",
        //         "clinicalStatus": {
        //             "coding": [
        //                 {
        //                     "system": "http://terminology.hl7.org/CodeSystem/condition-clinical",
        //                     "code": "active",
        //                     "display": "Active"
        //                 }
        //             ]
        //         },
        //         "category": [
        //             {
        //                 "coding": [
        //                     {
        //                         "system": "http://terminology.hl7.org/CodeSystem/condition-category",
        //                         "code": "problem-list-item",
        //                         "display": "Problem List Item"
        //                     }
        //                 ]
        //             }
        //         ],
        //         "code": {
        //             "coding": [
        //                 {
        //                     "system": "http://snomed.info/sct",
        //                     "code": "359746009",
        //                     "display": "Patient's condition stable"
        //                 }
        //             ]
        //         },
        //         "subject": {
        //             "reference": "Patient/{{Patient_ID}}",
        //             "display": "{{Patient_Name}}"
        //         },
        //         "encounter": {
        //             "reference": "urn:uuid:{{Encounter_id}}",
        //             "display": "Kunjungan {{Patient_Name}} di hari Kamis, 31 Agustus 2023"
        //         }
        //     },
        //     "request": {
        //         "method": "POST",
        //         "url": "Condition"
        //     }
        // }
        // ];

        $bundle = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => $data,
        ];

        return $bundle;
    }

    public static function rajalBundle(array $body)
    {
        // dd('a');
        try {

            $token = AccessToken::token();
            $url = ConfigSatuSehat::setUrl();

            $encounter_id = Str::uuid();

            $noreg = $body['noreg'];
            $reg_tgl = RajalService::wibToUTC($body['reg_tgl']);
            $discharge_tgl = $body['discharge_tgl'];

            $location_ihs = $body['location_ihs'];
            $location_nama = $body['location_nama'];

            $patient_ihs = $body['patient_ihs'];
            $patient_nama = $body['patient_nama'];

            $practitioner_ihs = $body['practitioner_ihs'];
            $practitioner_nama = $body['practitioner_nama'];

            $org_id = $body['org_id'];

            $asper_nadi = $body['asper']['nadi'];
            $asper_created_at = $body['asper']['created_at'];
            $diagnosa_primary = $body['diagnosa_primary'];
            $diagnosa_secondary = $body['diagnosa_secondary'];
            $diagnosas = $body['diagnosas'];
            $prosedurs = $body['prosedurs'];

            // dd($body);
            $bodyRaw = RajalBundleService::rajalBundleBody(
                $encounter_id,
                $noreg,
                $reg_tgl,
                $discharge_tgl,
                $location_ihs,
                $location_nama,
                $patient_ihs,
                $patient_nama,
                $practitioner_ihs,
                $practitioner_nama,
                $org_id,
                $asper_nadi,
                $asper_created_at,
                $diagnosa_primary,
                $diagnosa_secondary,
                $diagnosas,
                $prosedurs
            );
            // dd($bodyRaw);
            // $jsonData = json_encode($bodyRaw, JSON_PRETTY_PRINT);

            $httpClient = new Client(
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                    ],
                    'json' => $bodyRaw,
                ]
            );

            $response = $httpClient->post($url);
            if ($response->getStatusCode() != 200) {
                return null;
            }
            $data = $response->getBody()->getContents();

            // return json_decode($data, true);
            return $data['id'];
        } catch (\Throwable $e) {
            // return null;
            dd($e->getMessage());
        }
    }
}
