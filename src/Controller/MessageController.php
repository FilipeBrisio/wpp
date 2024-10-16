<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Service\PhoneNumberService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\EvolutionApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use GuzzleHttp\Exception\RequestException;
use App\Service\NumberProvider;
use App\Service\DynamoDbService;


class MessageController extends AbstractController
{
    private $numberProvider;
    private $phoneNumberService;
    private $evolutionApiService;
    private $dynamoDbService;



    public function __construct(NumberProvider $numberProvider, PhoneNumberService $phoneNumberService, EvolutionApiService $evolutionApiService,DynamoDbService $dynamoDbService)
    {
        $this->numberProvider = $numberProvider;
        $this->phoneNumberService = $phoneNumberService;
        $this->evolutionApiService = $evolutionApiService;
        $this->dynamoDbService = $dynamoDbService;
       ; 
    }
    #[Route('/send-message', name: 'send_message', methods:['POST'])]
    public function sendTextMessage(Request $request, LoggerInterface $logger): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    $numbers = $data['numbers'] ?? [];
    $textMessage = $data['textMessage']['text'] ?? null;

    if (empty($numbers) || !$textMessage) {
        $logger->error('Invalid request body format', ['data' => $data]);
        return new JsonResponse(['message' => 'Invalid request body format'], 400);
    }

    $responses = [];
    $batchSize = 7; 

   
    $batches = array_chunk($numbers, $batchSize);

    foreach ($batches as $batch) {
        foreach ($batch as $number) {
            try {
                $logger->info('Sending message to number: ' . $number);

                $response = $this->evolutionApiService->sendMessage($number, $textMessage);
                $responseBody = $response->getBody()->getContents();
                $statusCode = $response->getStatusCode();
                $responseData = json_decode($responseBody, true);

                if ($statusCode == 200) {
                    $logger->info('Message sent successfully to: ' . $number);
                    $responses[$number] = $responseData;
                } else {
                    $logger->warning('Message sent but with a non-200 status code to: ' . $number, [
                        'status_code' => $statusCode,
                        'response_body' => $responseBody
                    ]);
                    $responses[$number] = ['message' => 'Success'];
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();
                $statusCode = $response ? $response->getStatusCode() : null;
                $responseBody = $response ? $response->getBody()->getContents() : null;

                $logger->error('Failed to send message to: ' . $number, [
                    'error_message' => $e->getMessage(),
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'request_uri' => $e->getRequest()->getUri()
                ]);

                $responses[$number] = [
                    'message' => 'Failed to send message',
                    'error' => $e->getMessage(),
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                ];
            }
        }

        $logger->info('Waiting for 5 second before sending the next batch...');
        sleep(5);
    }

    return new JsonResponse($responses);
}


    #[Route('/send-group', name: 'send_group',  methods:['POST'])]
    public function test(Request $request): Response
    {
        $jsonData = json_decode($request->getContent(), true);

        $numbers = $this->numberProvider->getNumbers();
        $textMessage = $jsonData['textMessage'];

 

        $responses = [];

        foreach ($numbers as $number) {
            try {
                // Usa o serviço EvolutionApiService para enviar a mensagem
                $response = $this->evolutionApiService->sendMessage($number, $textMessage);

                $statusCode = $response->getStatusCode();
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 200) {
                    $responses[$number] = $responseData;
                } else {
                    $responses[$number] = [
                        'message' => 'Message sent',
                    ];
                }
            } catch (RequestException $e) {
                $responses[$number] = [
                    'message' => 'Failed to send message',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->json($responses);
    }

  
    #[Route('/webhook', name: 'webhook_route', methods:['POST'])]
    public function receiveMessage(Request $request, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
    
        $remoteJid = $data['data']['key']['remoteJid'] ?? null;
        $receivedMessage = $data['data']['message']['conversation'] 
            ?? $data['data']['message']['extendedTextMessage']['text'] 
            ?? null;
        $fromMe = $data['data']['key']['fromMe'] ?? null;
    
        if (!$remoteJid) {
            return new JsonResponse([
                'message' => 'Invalid request body format',
                'remoteJid' => null
            ], 400);
        }
    
        // Formata o remoteJid utilizando o serviço
        $formattedRemoteJid = $this->phoneNumberService->formatRemoteJid($remoteJid);
    
        $logMessage = sprintf("Received message from %s (formatted as %s)", $remoteJid, $formattedRemoteJid);
        $logger->info($logMessage);
        file_put_contents('webhook.log', $logMessage . PHP_EOL, FILE_APPEND);
    
        if ($fromMe) {
            return new JsonResponse([
                'message' => 'Message sent by sender, no action needed',
                'remoteJid' => $formattedRemoteJid
            ], 200);
        }

        $remoteJidWithoutSuffix = strstr($formattedRemoteJid, '@', true);
        $fixedId = 'Cliente_ID';
        $dynamoDbClient = $this->dynamoDbService->getClient();
    
        try {
            $result = $dynamoDbClient->query([
                'TableName' => 'Monitoramento',
                'KeyConditionExpression' => '#id = :id',
                'FilterExpression' => 'contains(#numeros, :numero)',
                'ExpressionAttributeNames' => [
                    '#id' => 'ID',
                    '#numeros' => 'Numeros'
                ],
                'ExpressionAttributeValues' => [
                    ':id' => ['S' => $fixedId],
                    ':numero' => ['S' => $remoteJidWithoutSuffix] 
                ]
            ]);
            $items = $result->get('Items');
            $logger->info('DynamoDB query result', ['items' => $items]);
    
            if (!empty($items)) {
                return $this->redirectToRoute('envio_automatico', [
                    'remoteJid' => $formattedRemoteJid,
                    'receivedMessage' => $receivedMessage,
                ]);
            } else {
                return new JsonResponse([
                    'message' => 'Número não encontrado no banco de dados',
                    'remoteJid' => $remoteJidWithoutSuffix
                ], 404);
            }
        } catch (\Aws\Exception\AwsException $e) {
            $logger->error('AWS error querying DynamoDB', [
                'error_message' => $e->getAwsErrorMessage(),
                'error_code' => $e->getAwsErrorCode(),
                'request_id' => $e->getAwsRequestId()
            ]);
            return new JsonResponse([
                'message' => 'AWS error querying DynamoDB',
                'error_message' => $e->getAwsErrorMessage(),
                'error_code' => $e->getAwsErrorCode(),
                'request_id' => $e->getAwsRequestId()
            ], 500);
        } catch (\Exception $e) {
            $logger->error('Error querying DynamoDB', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
            return new JsonResponse([
                'message' => 'Internal server error',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 500);
        }
    }
    
}