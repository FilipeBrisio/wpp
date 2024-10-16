<?php

namespace App\Service;

use GuzzleHttp\Exception\RequestException;

class MessageHandlerService
{
    private $evolutionApiService;
    private $catchService;

    public function __construct(EvolutionApiService $evolutionApiService, catchService $catchService)
    {
        $this->evolutionApiService = $evolutionApiService;
        $this->catchService = $catchService;
    }

    public function handleMessage(string $remoteJid, string $receivedMessage): array
    {
        $logMessage = 'handleMessage called with remoteJid: ' . $remoteJid . ' and receivedMessage: ' . $receivedMessage;
        $this->logMessage($logMessage);

        $remoteJidWithoutSuffix = strstr($remoteJid, '@', true);

        return $this->processReceivedMessage($remoteJidWithoutSuffix, $receivedMessage);
    }

    private function processReceivedMessage(string $remoteJid, string $receivedMessage): array
    {
        // Tratar toda mensagem recebida como se fosse um EndToEndId
        $responseMessage = $this->catchService->processEndToEndId($remoteJid, $receivedMessage);

        return $this->sendResponseMessage($remoteJid, $responseMessage);
    }

    private function sendResponseMessage(string $remoteJid, string $responseMessage): array
    {
        try {
            $this->logMessage('Sending response message via EvolutionApiService');
            $response = $this->evolutionApiService->sendMessage($remoteJid, $responseMessage);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($statusCode == 201) {
                $this->logMessage('Response message sent successfully');
                return ['status' => 'success', 'response' => $responseData];
            } else {
                $this->logMessage('Unexpected status code for response message: ' );
                return ['status' => 'success', 'message' => 'Response message sent successfully'];
            }
        } catch (RequestException $e) {
            $this->logMessage('Failed to send response message: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send response message', 'error' => $e->getMessage()];
        }
    }

    private function logMessage(string $message): void
    {
        $logFile = __DIR__ . '/../../public/webhook.log'; 
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    }
}
