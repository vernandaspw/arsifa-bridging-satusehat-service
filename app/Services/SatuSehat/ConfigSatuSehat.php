<?php

namespace App\Services\SatuSehat;

use Dotenv\Dotenv;

class ConfigSatuSehat
{

    protected $baseUrl;
    protected $authUrl;
    protected $clientId;
    protected $clientSecret;
    protected $organizationId;

    public function __construct()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
    }

    public static function setUrl()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
        return env('SATU_SEHAT_BASE_URL');
    }

    public static function setAuthUrl()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
        return env('SATU_SEHAT_AUTH_URL');
    }

    public static function setClientId()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
        return env('SATU_SEHAT_CLIENT_ID');
    }

    public static function setClientSecret()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
        return env('SATU_SEHAT_CLIENT_SECRET');
    }

    public static function setOrganizationId()
    {
        $dotenv = Dotenv::createUnsafeImmutable(getcwd());
        $dotenv->safeLoad();
        return env('SATU_SEHAT_ORGANIZATION_ID');
    }
}
