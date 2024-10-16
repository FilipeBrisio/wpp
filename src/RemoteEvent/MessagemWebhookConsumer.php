<?php

namespace App\RemoteEvent;

use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class MessagemWebhookConsumer
{
    private ClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function __invoke(RemoteEvent $event): JsonResponse
    {
        $payload = $event->getPayload();
        $message = $payload['message'] ?? '';
        $from = $payload['from'] ?? '';

        $responseText = '';

        if (strpos($message, 'oi') !== false) {
            $responseText = 'Olá! Como posso ajudar você?';
        } elseif (strpos($message, 'ajuda') !== false) {
            $responseText = 'Aqui estão algumas opções de ajuda: ...';
        } else {
            $responseText = 'Desculpe, não entendi. Pode reformular a pergunta?';
        }

        // Log para depuração
        $this->logger->info("Mensagem recebida de: $from", ['mensagem' => $message, 'resposta' => $responseText]);

        // Enviar a resposta de volta para o número de telefone via Evolution API
        $response = $this->client->request('POST', '/send-message', [
            'json' => [
                'to' => $from,
                'message' => $responseText,
            ],
            'headers' => [
                'Authorization' => 'Bearer <seu_token>',
            ],
        ]);

        // Verifique a resposta, se necessário
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException('Falha ao enviar mensagem via Evolution API.');
        }

        return new JsonResponse(['response' => $responseText]);
    }
}
