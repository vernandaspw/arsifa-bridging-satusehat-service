<?php

namespace App\Services\SatuSehat;

use GuzzleHttp\Client;

class LocationService
{

    protected static function bodyRawPost(array $body)
    {
        // get id location index
        // $location = Location::where('id', 1)->first();
        // $locationId = $location->location_id;

        $data = [
            "resourceType" => "Location",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/location/" . ConfigSatuSehat::setOrganizationId(),
                    "value" => $body['identifier_value'],
                ],
            ],
            "status" => $body['status'],
            "name" => $body['name'],
            "description" => $body['description'],
            "mode" => $body['mode'],
            "physicalType" => [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/location-physical-type",
                        "code" => $body['coding_code'],
                        "display" => $body['coding_display'],
                    ],
                ],
            ],
            "managingOrganization" => [
                "reference" => "Organization/" . $body['organization_id'],
            ],
        ];

        $additionalData = [];

        // Tambahkan elemen "id" berdasarkan kondisi ke dalam array tambahan
        if (isset($body['id'])) {
            $additionalData["id"] = $body['id'];
        }
        // Tambahkan elemen "partOf" ke dalam array tambahan
        if ($body['part_of']) {
            $additionalData["partOf"] = [
                "reference" => "Location/" . $body['part_of'],
            ];
        }
        // Gabungkan array tambahan ke dalam array utama
        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected static function bodyRawPatch(array $body)
    {
        $data = [
            [
                "op" => "replace",
                "path" => "/status",
                "value" => $body['status'],
            ],
            [
                "op" => "replace",
                "path" => "/identifier/0/value",
                "value" => $body['identifier_value'],
            ],
            [
                "op" => "replace",
                "path" => "/name",
                "value" => $body['name'],
            ],
            [
                "op" => "replace",
                "path" => "/description",
                "value" => $body['description'],
            ],
            [
                "op" => "replace",
                "path" => "/physicalType/coding/0/code",
                "value" => $body['coding_code'],
            ],
            [
                "op" => "replace",
                "path" => "/physicalType/coding/0/display",
                "value" => $body['coding_display'],
            ],
            [
                "op" => "replace",
                "path" => "/managingOrganization/reference",
                "value" => "Organization/" . $body['organization_id'],
            ],
            [
                "op" => "replace",
                "path" => "/partOf/reference",
                "value" => "Location/" . $body['part_of'],
            ],
        ];

        return $data;
    }

    protected function processParams($params)
    {
        if (isset($params['id'])) {
            $params['id'] = $params['id'];
        }

        if (isset($params['organization'])) {
            $params['organization'] = $params['organization'];
        }

        if (isset($params['name'])) {
            $params['name'] = $params['name'];
        }

        if (isset($params['partOf'])) {
            $params['partof'] = $params['partOf'];
        }

        return $params;
    }

    public static function getByID($id)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . 'Location/' . $id;

            $client = new Client();
            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = $response->getBody()->getContents();
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(array $body)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . 'Location';

            $bodyRaw = LocationService::bodyRawPost($body);
            $client = new Client();
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $bodyRaw,
            ]);

            $data = $response->getBody()->getContents();
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function update($endpoint, array $body)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . $endpoint;

            $bodyRaw = LocationService::bodyRawPost($body);
            $client = new Client();
            $response = $client->put($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $bodyRaw,
            ]);

            $data = $response->getBody()->getContents();
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function patchRequest($endpoint, array $body)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . $endpoint;

            $bodyRaw = LocationService::bodyRawPatch($body);
            $client = new Client();
            $response = $client->patch($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json-patch+json',
                ],
                'json' => $bodyRaw,
            ]);

            $data = $response->getBody()->getContents();
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'error',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
