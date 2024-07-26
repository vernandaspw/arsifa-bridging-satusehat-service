<?php

namespace App\Services\SatuSehat;

use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class RajalService
{
    protected function bodyPost(array $body)
    {
        $waktuWIB = date('Y-m-d\TH:i:sP', time());
        $dateTimeWIB = new DateTime($waktuWIB);
        $dateTimeWIB->modify("-7 hours");
        $waktuUTC = $dateTimeWIB->format('Y-m-d\TH:i:sP');

        $data = [
            "resourceType" => "Encounter",
            "status" => $body['status'],
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "AMB",
                "display" => "ambulatory",
            ],
            "subject" => [
                "reference" => "Patient/" . $body['patientId'],
                "display" => $body['patientName'],
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
                        "reference" => "Practitioner/" . $body['practitionerIhs'],
                        "display" => $body['practitionerName'],
                    ],
                ],
            ],
            "period" => [
                "start" => $waktuUTC,
            ],
            "location" => [
                [
                    "location" => [
                        "reference" => "Location/" . $body['locationId'],
                        "display" => $body['locationName'],
                    ],
                ],
            ],
            "statusHistory" => [
                [
                    "status" => $body['statusHistory'],
                    "period" => [
                        "start" => $waktuUTC,
                    ],
                ],
            ],
            "serviceProvider" => [
                "reference" => "Organization/" . $this->config->setOrganizationId(),
            ],
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/encounter/" . $this->config->setOrganizationId(),
                    "value" => $body['kodeReg'],
                ],
            ],
        ];

        return $data;
    }

    protected function bodyPatch(array $body)
    {
    }

    protected function processParams($param)
    {
    }

    public function getRequest($endpoint, $params = [])
    {
    }

    public function postRequest($endpoint, array $body)
    {
        $token = $this->accessToken->token();

        $url = $this->config->setUrl() . $endpoint;

        $bodyRaw = $this->bodyPost($body);

        $response = $this->httpClient->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => $bodyRaw,
        ]);

        $data = $response->getBody()->getContents();
        return json_decode($data, true);
    }
    protected static function bodyPostEncounterCondition(array $body, $tipe)
    {
        $waktuWIB = date('Y-m-d\TH:i:sP', time());
        $dateTimeWIB = new DateTime($waktuWIB);
        $dateTimeWIB->modify("-7 hours");
        $waktuUTC = $dateTimeWIB->format('Y-m-d\TH:i:sP');

        $uuidEncounter = Str::uuid();

        // diagnosis
        $diagnosis = [];
        $diagnosis_data = [];
        $diagnosaUUID = Str::uuid();
        if (!empty($body['diagnosas'])) {
            // dd($body['diagnosas']);
            foreach ($body['diagnosas'] as $indexDiagnosa => $diagnosa) {
                $item_data = [
                    'uuid' => $indexDiagnosa == 0 ? $diagnosaUUID : strval(Str::uuid()),
                    "code" => $diagnosa['pdiag_diagnosa'] ? $diagnosa['pdiag_diagnosa'] : '-',
                    'name' => '-',
                ];
                $diagnosis_data[] = $item_data;
                $item = [
                    "condition" => [
                        "reference" => "urn:uuid:" . $item_data['uuid'],
                        "display" => $diagnosa['pdiag_diagnosa'] ? $diagnosa['pdiag_diagnosa'] : '-',
                    ],
                    "use" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                "code" => 'DD',
                                "display" => "Discharge diagnosis",
                            ],
                        ],
                    ],
                    "rank" => $indexDiagnosa + 1,
                ];
                $diagnosis[] = $item;

            }
        }

        if ($tipe == 'rajal') {
            $actCode = 'AMB';
            $actDisplay = 'ambulatory';
        } elseif ($tipe == 'igd') {
            $actCode = 'EMER';
            $actDisplay = 'emergency';
        } elseif ($tipe == 'ranap') {
            $actCode = 'IMP';
            $actDisplay = 'inpatient encounter';
        }

        $encounter = [
            "fullUrl" => "urn:uuid:" . $uuidEncounter,
            "resource" => [
                "resourceType" => "Encounter",
                "status" => "finished",
                "class" => [
                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code" => $actCode,
                    "display" => $actDisplay,
                ],
                "subject" => [
                    "reference" => "Patient/" . $body['patientId'],
                    "display" => $body['patientName'],
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
                            "reference" => "Practitioner/" . $body['practitionerIhs'],
                            "display" => $body['practitionerName'],
                        ],
                    ],
                ],
                "period" => [
                    "start" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['RegistrationDateTime'])->setTimezone('UTC')->toIso8601String(),
                    "end" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['DischargeDateTime'])->setTimezone('UTC')->toIso8601String(),
                ],
                "location" => [
                    [
                        "location" => [
                            "reference" => "Location/" . $body['locationId'],
                            "display" => $body['locationName'] ? $body['locationName'] : '-',
                        ],
                    ],
                ],

                "diagnosis" => $diagnosis,
                "statusHistory" => [
                    [
                        "status" => "arrived",
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['RegistrationDateTime'])->setTimezone('UTC')->toIso8601String(),
                            "end" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['RegistrationDateTime'])->setTimezone('UTC')->toIso8601String(),
                        ],
                    ],
                    [
                        "status" => "in-progress",
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['RegistrationDateTime'])->setTimezone('UTC')->toIso8601String(),
                            "end" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['RegistrationDateTime'])->setTimezone('UTC')->toIso8601String(),
                        ],
                    ],
                    [
                        "status" => "finished",
                        "period" => [
                            "start" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['DischargeDateTime'])->setTimezone('UTC')->toIso8601String(),
                            "end" => Carbon::createFromFormat('Y-m-d H:i:s.u', $body['DischargeDateTime'])->setTimezone('UTC')->toIso8601String(),
                        ],
                    ],
                ],
                "hospitalization" => [
                    "dischargeDisposition" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                "code" => "oth",
                                "display" => "other-hcf",
                            ],
                        ],
                        "text" => "Rujukan ke RSUD SITI FATIMAH dengan nomor rujukan",
                    ],
                ],
                "serviceProvider" => [
                    "reference" => "Organization/" . env('SATU_SEHAT_ORGANIZATION_ID'),
                ],
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/encounter/" . env('SATU_SEHAT_ORGANIZATION_ID'),
                        "value" => $body['kodeReg'],
                    ],
                ],
            ],
            "request" => [
                "method" => "POST",
                "url" => "Encounter",
            ],
        ];

        // conditions
        $conditions = [];
        if (!empty($diagnosis) && !empty($diagnosis_data)) {
            foreach ($diagnosis_data as $diagnosisItem) {
                // dd($diagnosisItem);
                $condition = [
                    // "fullUrl" => "urn:uuid:" . substr($diagnosisItem['data']['condition']['reference'], strlen("urn:uuid:")),
                    "fullUrl" => "urn:uuid:" . $diagnosisItem['uuid'],
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
                                    "code" => $diagnosisItem['code'],
                                    "display" => $diagnosisItem['name'],
                                ],
                            ],
                        ],
                        "subject" => [
                            "reference" => "Patient/" . $body['patientId'],
                            "display" => $body['patientName'],
                        ],
                        "encounter" => [
                            "reference" => "urn:uuid:" . $uuidEncounter,
                            "display" => "Kunjungan " . $body['patientName'],
                        ],
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Condition",
                    ],
                ];
                $conditions[] = $condition;
            }
        }

        $observationNadi = [];
        if (!empty($body['observationNadi'])) {
            $observationNadi =
                [
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
                        "reference" => "Patient/" . $body['patientId'],
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/" . $body['practitionerIhs'],
                        ],
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:" . $uuidEncounter,
                        "display" => "Pemeriksaan Fisik Nadi ",
                    ],
                    "effectiveDateTime" => $body['observationNadi']['date'] ? (DateTime::createFromFormat('Y-m-d H:i:s.u', $body['observationNadi']['date']))->setTimezone(new DateTimeZone('-07:00'))->format('Y-m-d\TH:i:sP') : '',
                    "issued" => $body['observationNadi']['date'] ? (DateTime::createFromFormat('Y-m-d H:i:s.u', $body['observationNadi']['date']))->setTimezone(new DateTimeZone('-07:00'))->format('Y-m-d\TH:i:sP') : '',
                    "valueQuantity" => [
                        "value" => intval($body['observationNadi']['value']),
                        "unit" => "beats/minute",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "/min",
                    ],
                ],
                "request" => [
                    "method" => "POST",
                    "url" => "Observation",
                ],
            ];
        }
        // dd($observationNadi);
        $procedures = [];
        if (!empty($body['procedures'])) {
            foreach ($body['procedures'] as $key => $procedure) {
                $procedures[] =
                    [
                    "fullUrl" => "urn:uuid:" . Str::uuid(),
                    "resource" => [

                        "resourceType" => "Procedure",
                        "status" => "completed",
                        "category" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "103693007",
                                    "display" => "Diagnostic procedure",
                                ],
                            ],
                            "text" => "Diagnostic procedure",
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                    "code" => $procedure['pprosedur_prosedur'],
                                    "display" => "",
                                ],
                            ],
                        ],
                        "subject" => [
                            "reference" => "Patient/" . $body['patientId'],
                            "display" => $body['patientName'],
                        ],
                        "encounter" => [
                            "reference" => "Encounter/" . $uuidEncounter,
                            "display" => '',
                        ],
                        "performedPeriod" => [
                            "start" => $procedure['created_at'] ? date_format(date_create_from_format('Y-m-d H:i:s.u', $procedure['created_at']), 'Y-m-d\TH:i:sP') : null,
                            "end" => $procedure['created_at'] ? date_format(date_create_from_format('Y-m-d H:i:s.u', $procedure['created_at']), 'Y-m-d\TH:i:sP') : null,
                        ],
                        "performer" => [
                            [
                                "actor" => [
                                    "reference" => "Practitioner/" . $body['practitionerIhs'],
                                    "display" => $body['practitionerName'],
                                ],
                            ],
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://hl7.org/fhir/sid/icd-10",
                                        "code" => $body['diagnosa_utama']['pdiag_diagnosa'],
                                        "display" => "",
                                    ],
                                ],
                            ],
                        ],
                        "bodySite" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "302551006",
                                        "display" => "Entire Thorax",
                                    ],
                                ],
                            ],
                        ],
                        "note" => [
                            [
                                "text" => "",
                            ],
                        ],
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Procedure",
                    ],
                ];
            }
        }

        $medications = [];
        // if (!empty($body['medications'])) {
        //     foreach ($body['medications'] as $medication) {
        //         $racikan = $medication['temp_flag_racikan'] != 0 ? true : false;
        //         $kfa = $medication['kfa'];
        //         $itemCode = $medication['item_code'];
        //         $itemName =  $medication['item_name'];
        //     }
        //     $medication_id = Str::uuid();
        //     $medications = [
        //         [
        //             "fullUrl" => "urn:uuid:" . $medication_id,
        //             "resource" => [
        //                 "resourceType" => "Medication",
        //                 "meta" => [
        //                     "profile" => [
        //                         "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
        //                     ],
        //                 ],
        //                 "extension" => [
        //                     [
        //                         "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
        //                         "valueCodeableConcept" => [
        //                             "coding" => [
        //                                 [
        //                                     "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
        //                                     "code" => "NC",
        //                                     "display" => "Non-compound",
        //                                 ],
        //                             ],
        //                         ],
        //                     ],
        //                 ],
        //                 "identifier" => [
        //                     [
        //                         "use" => "official",
        //                         "system" => "http://sys-ids.kemkes.go.id/medication/". env('SATU_SEHAT_ORGANIZATION_ID'),
        //                         "value" => "123456789",
        //                     ],
        //                 ],
        //                 "code" => [
        //                     "coding" => [
        //                         [
        //                             "system" => "http://sys-ids.kemkes.go.id/kfa",
        //                             "code" => "" . $racikan ? null : $kfa,
        //                             "display" => $racikan ? null : $itemName,
        //                         ],
        //                     ],
        //                 ],
        //                 "status" => "active",
        //                 "manufacturer" => [
        //                     "reference" => "Organization/" . env('SATU_SEHAT_ORGANIZATION_ID'),
        //                 ],
        //                 "form" => [
        //                     "coding" => [
        //                         [
        //                             "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-form",
        //                             "code" => $itemCode,
        //                             "display" => $itemName,
        //                         ],
        //                     ],
        //                 ],

        //             ],
        //             "request" => [
        //                 "method" => "POST",
        //                 "url" => "Medication",
        //             ],
        //         ],
        //         [
        //             "fullUrl" => "urn:uuid:". Str::uuid(),
        //             "resource" => [
        //                 "resourceType" => "MedicationRequest",
        //                 "identifier" => [
        //                     [
        //                         "use" => "official",
        //                         "system" => "http://sys-ids.kemkes.go.id/prescription/" . env('SATU_SEHAT_ORGANIZATION_ID'),
        //                         "value" => "123456788",
        //                     ],
        //                     [
        //                         "use" => "official",
        //                         "system" => "http://sys-ids.kemkes.go.id/prescription-item/" . env('SATU_SEHAT_ORGANIZATION_ID'),
        //                         "value" => "123456788-1",
        //                     ],
        //                 ],
        //                 "status" => "completed",
        //                 "intent" => "order",
        //                 "category" => [
        //                     [
        //                         "coding" => [
        //                             [
        //                                 "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
        //                                 "code" => "outpatient",
        //                                 "display" => "Outpatient",
        //                             ],
        //                         ],
        //                     ],
        //                 ],
        //                 "priority" => "routine",
        //                 "medicationReference" => [
        //                     "reference" => "urn:uuid:" . $medication_id,
        //                     "display" => $itemName,
        //                 ],
        //                 "subject" => [
        //                     "reference" => "Patient/" .  $body['patientId'],
        //                     "display" => $body['patientName'],
        //                 ],
        //                 "encounter" => [
        //                     "reference" => "urn:uuid:". $uuidEncounter,
        //                 ],
        //                 "authoredOn" => $medication['created_at'],
        //                 "requester" => [
        //                     "reference" => "Practitioner/" .  $body['practitionerIhs'],
        //                     "display" => $body['practitionerName'],
        //                 ],
        //                 "reasonReference" => [
        //                     [
        //                         "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
        //                         "display" => "{{DiagnosisPrimer_Text}}",
        //                     ],
        //                 ],
        //                 "courseOfTherapyType" => [
        //                     "coding" => [
        //                         [
        //                             "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-course-of-therapy",
        //                             "code" => "continuous",
        //                             "display" => "Continuing long term therapy",
        //                         ],
        //                     ],
        //                 ],
        //                 "dosageInstruction" => [
        //                     [
        //                         "sequence" => 1,
        //                         "additionalInstruction" => [
        //                             [
        //                                 "coding" => [
        //                                     [
        //                                         "system" => "http://snomed.info/sct",
        //                                         "code" => "418577003",
        //                                         "display" => "Take at regular intervals. Complete the prescribed course unless otherwise directed",
        //                                     ],
        //                                 ],
        //                             ],
        //                         ],
        //                         "patientInstruction" => "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
        //                         "timing" => [
        //                             "repeat" => [
        //                                 "frequency" => 1,
        //                                 "period" => 1,
        //                                 "periodUnit" => "d",
        //                             ],
        //                         ],
        //                         "route" => [
        //                             "coding" => [
        //                                 [
        //                                     "system" => "http://www.whocc.no/atc",
        //                                     "code" => "O",
        //                                     "display" => "Oral",
        //                                 ],
        //                             ],
        //                         ],
        //                         "doseAndRate" => [
        //                             [
        //                                 "type" => [
        //                                     "coding" => [
        //                                         [
        //                                             "system" => "http://terminology.hl7.org/CodeSystem/dose-rate-type",
        //                                             "code" => "ordered",
        //                                             "display" => "Ordered",
        //                                         ],
        //                                     ],
        //                                 ],
        //                                 "doseQuantity" => [
        //                                     "value" => 4,
        //                                     "unit" => "TAB",
        //                                     "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                                     "code" => "TAB",
        //                                 ],
        //                             ],
        //                         ],
        //                     ],
        //                 ],
        //                 "dispenseRequest" => [
        //                     "dispenseInterval" => [
        //                         "value" => 1,
        //                         "unit" => "days",
        //                         "system" => "http://unitsofmeasure.org",
        //                         "code" => "d",
        //                     ],
        //                     "validityPeriod" => [
        //                         "start" => "2023-08-31T03:27:00+00:00",
        //                         "end" => "2024-07-22T14:27:00+00:00",
        //                     ],
        //                     "numberOfRepeatsAllowed" => 0,
        //                     "quantity" => [
        //                         "value" => 120,
        //                         "unit" => "TAB",
        //                         "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
        //                         "code" => "TAB",
        //                     ],
        //                     "expectedSupplyDuration" => [
        //                         "value" => 30,
        //                         "unit" => "days",
        //                         "system" => "http://unitsofmeasure.org",
        //                         "code" => "d",
        //                     ],
        //                     "performer" => [
        //                         "reference" => "Organization/{{Org_ID}}",
        //                     ],
        //                 ],
        //             ],
        //             "request" => [
        //                 "method" => "POST",
        //                 "url" => "MedicationRequest",
        //             ],
        //         ],
        //     ];
        // }
        $data = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" =>
            array_merge([$encounter],
                $conditions,
                !empty($observationNadi) ? [$observationNadi] : [],
                $procedures,
                // $medications
            ),
        ];
        // dd($data);

        return $data;
    }

    public static function PostEncounterCondition(array $body, $tipe)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl();

            $bodyRaw = self::bodyPostEncounterCondition($body, $tipe);
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
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return null;
            // dd($e->getMessage());
        }
    }

    static function wibToUTC($waktuWIB) {
        $dateTimeWIB = new DateTime($waktuWIB);
        $dateTimeWIB->modify("-7 hours");
        $waktuUTC = $dateTimeWIB->format('Y-m-d\TH:i:sP');
        return $waktuUTC;
    }

    public static function encounterKunjunganBaruBody(array $body)
    {
        $noreg = $body['noreg'];
        $reg_tgl = RajalService::wibToUTC($body['reg_tgl']);
        $location_id = $body['location_id'];
        $location_name = $body['location_name'];
        $patient_id = $body['patient_id'];
        $patient_name = $body['patient_name'];
        $practitioner_id = $body['practitioner_id'];
        $practitioner_name = $body['practitioner_name'];

        $uuidEncounter = Str::uuid();

        $raw = [
            "resourceType" => "Encounter",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/encounter/" . env('SATU_SEHAT_ORGANIZATION_ID'),
                    "value" => $noreg,
                ],
            ],
            "status" => "arrived",
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "AMB",
                "display" => "ambulatory",
            ],
            "subject" => [
                "reference" => "Patient/". $patient_id,
                "display" => $patient_name,
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
                        "reference" => "Practitioner/". $practitioner_id,
                        "display" => $practitioner_name,
                    ],
                ],
            ],
            "period" => [
                "start" => $reg_tgl,
            ],
            "location" => [
                [
                    "location" => [
                        "reference" => "Location/". $location_id,
                        "display" => $location_name,
                    ],
                    "period" => [
                        "start" => $reg_tgl,
                    ],
                    "extension" => [
                        [
                            "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
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
                                // [
                                //     "url" => "upgradeClassIndicator",
                                //     "valueCodeableConcept" => [
                                //         "coding" => [
                                //             [
                                //                 "system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                //                 "code" => "kelas-tetap",
                                //                 "display" => "Kelas Tetap Perawatan",
                                //             ],
                                //         ],
                                //     ],
                                // ],
                            ],
                        ],
                    ],
                ],
            ],
            "statusHistory" => [
                [
                    "status" => "arrived",
                    "period" => [
                        "start" => $reg_tgl,
                    ],
                ],
            ],
            "serviceProvider" => [
                "reference" => "Organization/" . env('SATU_SEHAT_ORGANIZATION_ID'),
            ],
        ];

        return $raw;
    }
    public static function encounterKunjunganBaru(array $body)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . '/Encounter';

            $bodyRaw = RajalService::encounterKunjunganBaruBody($body);
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
            return null;
            // dd($e->getMessage());
        }
    }

    public static function encounterMasukRuangBody(array $body, $encounter_id)
    {
        $noreg = $body['noreg'];
        $reg_tgl = RajalService::wibToUTC($body['reg_tgl']);
        $location_id = $body['location_id'];
        $location_name = $body['location_name'];
        $patient_id = $body['patient_id'];
        $patient_name = $body['patient_name'];
        $practitioner_id = $body['practitioner_id'];
        $practitioner_name = $body['practitioner_name'];

        $uuidEncounter = Str::uuid();

        $raw = [
            "resourceType"=> "Encounter",
            "id"=> "{{Encounter_id}}",
            "identifier"=> [
                [
                    "system"=> "http://sys-ids.kemkes.go.id/encounter/{{Org_ID}}",
                    "value"=> "{{Encounter_Registration_ID}}"
                ]
            ],
            "status"=> "in-progress",
            "class"=> [
                "system"=> "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code"=> "AMB",
                "display"=> "ambulatory"
            ],
            "subject"=> [
                "reference"=> "Patient/{{Patient_ID}}",
                "display"=> "{{Patient_Name}}"
            ],
            "participant"=> [
                [
                    "type"=> [
                        [
                            "coding"=> [
                                [
                                    "system"=> "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                    "code"=> "ATND",
                                    "display"=> "attender"
                                ]
                            ]
                        ]
                    ],
                    "individual"=> [
                        "reference"=> "Practitioner/{{Practitioner_ID}}",
                        "display"=> "{{Practitioner_Name}}"
                    ]
                ]
            ],
            "period"=> [
                "start"=> "2023-08-31T01:00:00+00:00"
            ],
            "location"=> [
                [
                    "location"=> [
                        "reference"=> "Location/{{Location_Poli_id}}",
                        "display"=> "{{Location_Poli_Name}}"
                    ],
                    "period"=> [
                        "start"=> "2023-08-31T00:00:00+00:00"
                    ],
                    "extension"=> [
                        [
                            "url"=> "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                            "extension"=> [
                                [
                                    "url"=> "value",
                                    "valueCodeableConcept"=> [
                                        "coding"=> [
                                            [
                                                "system"=> "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                "code"=> "reguler",
                                                "display"=> "Kelas Reguler"
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    "url"=> "upgradeClassIndicator",
                                    "valueCodeableConcept"=> [
                                        "coding"=> [
                                            [
                                                "system"=> "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                "code"=> "kelas-tetap",
                                                "display"=> "Kelas Tetap Perawatan"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "statusHistory"=> [
                [
                    "status"=> "arrived",
                    "period"=> [
                        "start"=> "2023-08-31T00:00:00+00:00",
                        "end"=> "2023-08-31T01:00:00+00:00"
                    ]
                ],
                [
                    "status"=> "in-progress",
                    "period"=> [
                        "start"=> "2023-08-31T01:00:00+00:00"
                    ]
                ]
            ],
            "serviceProvider"=> [
                "reference"=> "Organization/{{Org_ID}}"
            ]
        ];

        return $raw;
    }
    public static function encounterMasukRuang(array $body, $encounter_id)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . '/Encounter';

            $bodyRaw = RajalService::encounterMasukRuangBody($body, $encounter_id);
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
            return null;
            // dd($e->getMessage());
        }
    }
}
