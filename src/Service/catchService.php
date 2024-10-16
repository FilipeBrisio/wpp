<?php

namespace App\Service;

use GuzzleHttp\Exception\RequestException;

class catchService
{
    private $evolutionApiService;
    private $bancoService;

    public function __construct(EvolutionApiService $evolutionApiService, bancoService $bancoService)
    {
        $this->evolutionApiService = $evolutionApiService;
        $this->bancoService = $bancoService;
    }



    public function processEndToEndId(string $remoteJid, string $endToEndId): string
    {
        $this->logMessage('Processing EndToEndId: ' . $endToEndId);

        // Enviar o End to End ID para o BancoService para processamento
        $result = $this->bancoService->execute($endToEndId);

       
        if ($result['status'] === 'success') {
            return $result['message']; 
        } else {
            $errorMessage = $result['message'] ?? 'EndtoEndID processing failure.';
            return $errorMessage;
        }
    }

    private function logMessage(string $message): void
    {
        $logFile = __DIR__ . '/../../public/catch_service.log'; 
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    }
}
