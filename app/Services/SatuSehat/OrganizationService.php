<?php

namespace App\Services\SatuSehat;

use GuzzleHttp\Client;

class OrganizationService
{

    protected static function bodyPost(array $body)
    {
        $data = [
            "resourceType" => "Organization",
            "active" => $body['active'],
            "identifier" => [
                [
                    "use" => "official",
                    "system" => "http://sys-ids.kemkes.go.id/organization/" . ConfigSatuSehat::setOrganizationId(),
                    "value" => $body['identifier_value'],
                ],
            ],
            "type" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/organization-type",
                            "code" => $body['coding_code'],
                            "display" => $body['coding_display'],
                        ],
                    ],
                ],
            ],
            "name" => $body['name'],
        ];

        $additionalData = [];

        // Tambahkan elemen "id" berdasarkan kondisi ke dalam array tambahan
        if (isset($body['id'])) {
            $additionalData["id"] = $body['id'];
        }

        // Tambahkan elemen "partOf" ke dalam array tambahan
        if ($body['part_of']) {
            $additionalData["partOf"] = [
                "reference" => "Organization/" . $body['part_of'],
            ];
        }
        // Gabungkan array tambahan ke dalam array utama
        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function bodyPatch(array $body)
    {
        $data = [
            [
                "op" => "replace",
                "path" => "/active",
                "value" => $body['active'],
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
                "path" => "/partOf/reference",
                "value" => "Organization/" . $body['part_of'],
            ],
            [
                "op" => "replace",
                "path" => "/type/0/coding/0/code",
                "value" => $body['coding_code'],
            ],
            [
                "op" => "replace",
                "path" => "/type/0/coding/0/display",
                "value" => $body['coding_display'],
            ],
        ];

        return $data;
    }

    protected static function processParams($params)
    {
        if (isset($params['id'])) {
            $params['id'] = $params['id'];
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
            $url = ConfigSatuSehat::setUrl() . 'Organization/' . $id;

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
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }

    }

    public static function create(array $body)
    {

        try {
            $token = AccessToken::token();
            $url = ConfigSatuSehat::setUrl() . 'Organization';
            $client = new Client();

            $bodyRaw = OrganizationService::bodyPost($body);

            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'json' => $bodyRaw,
            ]);

            $data = $response->getBody()->getContents();

            return json_decode($data, true);
        } catch (\Throwable $e) {

            return response()->json([
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public static function update(array $body)
    {
        try {
            $token = AccessToken::token();

            $url = ConfigSatuSehat::setUrl() . 'Organization/' . $body;

            $bodyRaw = OrganizationService::bodyPost($body);
            $client = new Client();
            $response = $client->put($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'json' => $bodyRaw,
            ]);

            $data = $response->getBody()->getContents();
            return json_decode($data, true);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function patchRequest(array $body)
    {
        try {
            $token = AccessToken::token();
            $url = ConfigSatuSehat::setUrl() . 'Location/' . $body;

            $bodyRaw = OrganizationService::bodyPatch($body);
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
                'msg' => 'Terjadi kesalahan server satusehat',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
