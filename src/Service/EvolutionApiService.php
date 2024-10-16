<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class EvolutionApiService
{
    private $client;
    private $logger;
    private $apiKey;
    private $baseUri;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        
        $this->apiKey = $_ENV['EVOLUTION_API_KEY'] ?? 'default_key';
        $this->baseUri = $_ENV['EVOLUTION_API_URL'] ?? 'http://localhost:8080';

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Content-Type' => 'application/json',
                'apikey' => $this->apiKey,
            ],
        ]);

        $this->logger->info('EvolutionApiService initialized with baseUri: ' . $this->baseUri);
    }

    public function sendMessage(string $number, string $textMessage)
    {
        $this->logger->info('Sending message to number: ' . $number);

        try {
            $response = $this->client->post('/message/sendText/delbot', [
                'json' => [
                    'number' => $number,
                    'textMessage' => ['text' => $textMessage],
                ],
            ]);

            $this->logger->info('Message sent successfully');
            return $response;
        } catch (RequestException $e) {
            $this->logger->error('Failed to send message', ['exception' => $e]);
            throw $e;
        }
    }
}
