<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DbService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchApiData(string $url): array
    {
        try {
            $response = $this->client->request('GET', $url);
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            
            file_put_contents(__DIR__ . '/../../public/api_response.log', $responseBody . PHP_EOL, FILE_APPEND);

            $responseData = json_decode($responseBody, true);
            
            file_put_contents(__DIR__ . '/../../public/decoded_response.log', json_encode($responseData) . PHP_EOL, FILE_APPEND);

            if ($statusCode == 200 && $responseData !== null) {
                return ['status' => 'success', 'data' => $responseData];
            } else {
                return ['status' => 'error', 'message' => 'Not Found'];
            }
        } catch (RequestException $e) {
            return ['status' => 'error', 'message' => 'Failed to fetch data from API', 'error' => $e->getMessage()];
        }
    }
}
