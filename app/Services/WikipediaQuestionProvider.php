<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Http\Client;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use RuntimeException;

final class WikipediaQuestionProvider
{
    private const SUMMARY_URL = 'https://cs.wikipedia.org/api/rest_v1/page/random/summary';

    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    /**
     * @return array{question:string,options:array<int,string>,correctIndex:int,source:string}
     */
    public function fetchQuestion(): array
    {
        $correct = $this->downloadSummary();
        $decoys = [];
        $attempts = 0;
        while (count($decoys) < 2 && $attempts < 10) {
            $candidate = $this->downloadSummary();
            if ($candidate['title'] !== $correct['title']) {
                $decoys[] = $candidate;
            }
            $attempts++;
        }

        if (count($decoys) < 2) {
            throw new RuntimeException('Nepodařilo se načíst dostatek náhodných článků z Wikipedie.');
        }

        $extract = Strings::truncate(trim($correct['extract']), 360, '…');
        $questionText = sprintf(
            "Jak se jmenuje encyklopedické heslo popisované tímto shrnutím?\n\n%s",
            $extract !== '' ? $extract : 'Shrnutí nebylo k dispozici, zkuste správný název odhadnout.'
        );

        $options = [$correct['title'], $decoys[0]['title'], $decoys[1]['title']];
        $shuffled = $options;
        shuffle($shuffled);
        $correctIndex = array_search($correct['title'], $shuffled, true);

        return [
            'question' => $questionText,
            'options' => $shuffled,
            'correctIndex' => $correctIndex !== false ? (int) $correctIndex : 0,
            'source' => $correct['content_urls']['desktop']['page'] ?? 'https://cs.wikipedia.org/',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function downloadSummary(): array
    {
        $request = $this->client->createRequest(self::SUMMARY_URL);
        $response = $this->client->send($request);
        if (!$response->isOk()) {
            throw new RuntimeException('Wikipedie odpověděla chybou: ' . $response->getStatus());
        }

        /** @var array<string,mixed> $data */
        $data = Json::decode($response->getBody(), Json::FORCE_ARRAY);
        if (!isset($data['title'])) {
            throw new RuntimeException('Odpověď Wikipedie neobsahuje očekávaná data.');
        }

        return $data;
    }
}
