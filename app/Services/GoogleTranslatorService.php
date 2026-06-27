<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Traits\HasTranslationBatching;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;

class GoogleTranslatorService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(private ProviderCredential $credential) {}

    public function translateNmt(array $translatables, Language $source, Language $target): array
    {
        $credentials = json_decode((string) $this->credential->service_account, true);

        if (blank($this->credential->google_project_id) || ! is_array($credentials)) {
            throw new \RuntimeException('Google Cloud Translation is not configured.');
        }

        $client = new TranslationServiceClient(['credentials' => $credentials]);
        $results = [];

        try {
            foreach ($this->packIntoCharBatches($translatables, 25000) as $batch) {
                $request = (new TranslateTextRequest)
                    ->setParent($client->locationName($this->credential->google_project_id, 'global'))
                    ->setSourceLanguageCode($source->iso_2)
                    ->setTargetLanguageCode($target->iso_2)
                    ->setMimeType('text/html')
                    ->setContents(array_column($batch, 'text'));
                $translations = $client->translateText($request)->getTranslations();

                foreach ($batch as $index => $item) {
                    $results[] = [
                        'id' => $item['id'],
                        'text' => $item['text'],
                        'translated' => $translations[$index]?->getTranslatedText() ?? $item['text'],
                    ];
                }
            }
        } finally {
            $client->close();
        }

        return $results;
    }

    public function translateLlm(
        array $translatables,
        Language $source,
        Language $target,
        array $options = [],
    ): array {
        return $this->translateNmt($translatables, $source, $target);
    }
}
