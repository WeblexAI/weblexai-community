<?php

namespace App\Services\GeoIP;

use App\Settings\MaxmindSettings;
use GeoIp2\WebService\Client;
use Torann\GeoIP\Services\MaxMindWebService as BaseMaxMindWebService;

class MaxMindWebServiceService extends BaseMaxMindWebService
{
    public function boot()
    {
        $settings = app(MaxmindSettings::class);

        $this->client = new Client(
            $settings->user_id,
            $settings->license_key,
            $this->config('locales', ['en'])
        );
    }
}
