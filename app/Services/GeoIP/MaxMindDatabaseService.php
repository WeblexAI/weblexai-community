<?php

namespace App\Services\GeoIP;

use App\Settings\MaxmindSettings;
use GeoIp2\Database\Reader;
use Torann\GeoIP\Services\MaxMindDatabase as BaseMaxMindDatabase;

class MaxMindDatabaseService extends BaseMaxMindDatabase
{
    public function boot()
    {
        $path = $this->config('database_path');

        if (is_file($path) === false) {
            @mkdir(dirname($path));

            copy(__DIR__.'/../../../vendor/torann/geoip/resources/geoip.mmdb', $path);
        }

        $this->reader = new Reader(
            $path, $this->config('locales', ['en'])
        );
    }

    public function update()
    {
        if ($this->config('database_path', false) === false) {
            throw new \Exception('Database path not set in config file.');
        }

        $settings = app(MaxmindSettings::class);

        $updateUrl = sprintf(
            'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz',
            $settings->license_key
        );

        $this->withTemporaryDirectory(function ($directory) use ($updateUrl) {
            $tarFile = sprintf('%s/maxmind.tar.gz', $directory);

            file_put_contents($tarFile, fopen($updateUrl, 'r'));

            $archive = new \PharData($tarFile);

            $file = $this->findDatabaseFile($archive);

            $relativePath = "{$archive->getFilename()}/{$file->getFilename()}";

            $archive->extractTo($directory, $relativePath);

            file_put_contents($this->config('database_path'), fopen("{$directory}/{$relativePath}", 'r'));
        });

        return "Database file ({$this->config('database_path')}) updated.";
    }
}
