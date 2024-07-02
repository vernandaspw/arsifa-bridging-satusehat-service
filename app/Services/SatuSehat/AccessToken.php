<?php

namespace App\Services\SatuSehat;

use GuzzleHttp\Client;
use App\Services\SatuSehat\ConfigSatuSehat;

class AccessToken
{
    public static function token()
    {
        $httpClient = new Client();
        $response = $httpClient->post(ConfigSatuSehat::setAuthUrl() . '/accesstoken', [
            'query' => [
                'grant_type' => 'client_credentials',
            ],
            'form_params' => [
                'client_id' => ConfigSatuSehat::setClientId(),
                'client_secret' => ConfigSatuSehat::setClientSecret(),
            ],
        ]);

        $data = $response->getBody()->getContents();
        $result = json_decode($data, true);
        return $result['access_token'];
    }
}
