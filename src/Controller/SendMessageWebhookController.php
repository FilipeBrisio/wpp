<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\MessageHandlerService;
use Psr\Log\LoggerInterface;

class SendMessageWebhookController extends AbstractController
{
    private $messageHandlerService;
    private $logger;

    public function __construct(MessageHandlerService $messageHandlerService, LoggerInterface $logger)
    {
        $this->messageHandlerService = $messageHandlerService;
        $this->logger = $logger;
    }

    #[Route('/envioautomatico', name: 'envio_automatico', methods: ['GET', 'POST'])]
    public function envioAutomatico(Request $request): JsonResponse
    {
        $this->logger->info('envioAutomatico called');

        $remoteJid = $request->query->get('remoteJid');
        $receivedMessage = $request->query->get('receivedMessage');

        if (!$remoteJid || !$receivedMessage) {
            $this->logger->error('Invalid request parameters');
            return new JsonResponse(['message' => 'Invalid request parameters'], 400);
        }

        $response = $this->messageHandlerService->handleMessage($remoteJid, $receivedMessage);

        if ($response['status'] === 'success') {
            return new JsonResponse($response, 200);
        } else {
            $this->logger->error('Error response: ' . json_encode($response));
            return new JsonResponse($response, 500);
        }
    }
}