<?php

namespace App\Services\SatuSehat;

use GuzzleHttp\Client;

class PracticionerService
{

    protected function processParams($params)
    {

        if (isset($params['identifier'])) {
            $params['identifier'] = 'https://fhir.kemkes.go.id/id/nik|' . $params['identifier'];
        }
        if (isset($params['name'])) {
            $params['name'] = $params['name'];
        }

        if (isset($params['birthdate'])) {
            $params['birthdate'] = $params['birthdate'];
        }

        return $params;
    }

    public static function getByNIK($nik)
    {
        $token = AccessToken::token();

        $url = ConfigSatuSehat::setUrl() . 'Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;
        $client = new Client();
        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        $res = $response->getBody()->getContents();
        $data = json_decode($res, true);
        if (!empty($data['entry'])) {
            return $data;
        } else {
           return null;
        }
    }
}
