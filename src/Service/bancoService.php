<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class bancoService
{
    private $dbService;
    private $userStatusService;
    private $dynamoDbService;

    public function __construct(DbService $dbService, UserStatusService $userStatusService, DynamoDbService $dynamoDbService)
    {
        $this->dbService = $dbService;
        $this->userStatusService = $userStatusService;
        $this->dynamoDbService = $dynamoDbService;
    }

    public function execute(string $endToEndId): array
    {
        $url = 'https://xnmk9le5m7.execute-api.us-east-1.amazonaws.com/prd/api/v1/pix/' . $endToEndId;

        $dbResult = $this->dbService->fetchApiData($url);

        // Log do resultado da consulta
        file_put_contents(__DIR__ . '/../../public/db_result.log', json_encode($dbResult) . PHP_EOL, FILE_APPEND);

        if ($dbResult['status'] === 'success' && isset($dbResult['data'])) {
            $data = $dbResult['data'];
            $payerAccount = $data['payer']['number'] ?? null;
            $payeeAccount = $data['beneficiary']['number'] ?? null;

            if ($payerAccount && $payeeAccount) {
                $dynamoDbClient = $this->dynamoDbService->getClient();
                $resultPayer = $dynamoDbClient->scan([
                    'TableName' => 'Clientes',
                    'FilterExpression' => 'Conta = :payerAccount',
                    'ExpressionAttributeValues' => [
                        ':payerAccount' => ['S' => $payerAccount]
                    ]
                ]);
            
                $resultPayee = $dynamoDbClient->scan([
                    'TableName' => 'Clientes',
                    'FilterExpression' => 'Conta = :payeeAccount',
                    'ExpressionAttributeValues' => [
                        ':payeeAccount' => ['S' => $payeeAccount]
                    ]
                ]);

                $payerRecord = !empty($resultPayer['Items']) ? $resultPayer['Items'][0] : null;
                $payeeRecord = !empty($resultPayee['Items']) ? $resultPayee['Items'][0] : null;

                if ($payerRecord || $payeeRecord) {
                    $formattedMessage = $this->formatMessage($data);
                    $this->endMenuForUser($endToEndId);

                    return [
                        'status' => 'success',
                        'message' => $formattedMessage,
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'This EndToEndID does not belong to you',
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Insufficient data for validation',
                ];
            }
        } else {
            // Lidar com o erro
            $errorMessage = $dbResult['message'] ?? 'An unknown error has occurred';
            file_put_contents(__DIR__ . '/../../public/error.log', $errorMessage . PHP_EOL, FILE_APPEND);

            return [
                'status' => 'error',
                'message' => $errorMessage,
            ];
        }
    }

    private function formatMessage(array $data): string
    {
        $status = $data['status'] ?? 'N/A';
        $endToEndId = $data['endToEndId'] ?? 'N/A';
        $key = $data['key'] ?? 'N/A';
        $amount = $data['amount'] ?? 'N/A';
        $finishedAt = $data['finishedAt'] ?? 'N/A';

        $payerName = $data['payer']['holder']['name'] ?? 'N/A';
        $payerAccount = $data['payer']['number'] ?? 'N/A';
        $payerISPB = $data['payer']['participant']['ispb'] ?? 'N/A';
        $payerBankName = $data['payer']['participant']['name'] ?? 'N/A';

        $payeeName = $data['beneficiary']['holder']['name'] ?? 'N/A';
        $payeeAccount = $data['beneficiary']['number'] ?? 'N/A';
        $payeeISPB = $data['beneficiary']['participant']['ispb'] ?? 'N/A';
        $payeeBankName = $data['beneficiary']['participant']['name'] ?? 'N/A';

        $formattedMessage = "Here is a summary of the transaction:\n";
        $formattedMessage .= "Status: $status 🟢\n";
        $formattedMessage .= "End to end ID: $endToEndId\n";
        $formattedMessage .= "Pix key related: $key\n";
        $formattedMessage .= "Amount received: BRL $amount\n";
        $formattedMessage .= "-----------------\n";
        $formattedMessage .= "Payer Name that sent the money: $payerName\n";
        $formattedMessage .= "Payer Account that sent the money: $payerAccount\n";
        $formattedMessage .= "Payer ISPB (Bank code): $payerISPB - $payerBankName\n";
        $formattedMessage .= "-----------------\n";
        $formattedMessage .= "Payee Name that received the money: $payeeName\n";
        $formattedMessage .= "Payee Account that received the money: $payeeAccount\n";
        $formattedMessage .= "Payee ISPB (Bank code): $payeeISPB\n";
        $formattedMessage .= "-----------------\n";
        $formattedMessage .= "\n";
        $formattedMessage .= "In other words, $payeeName, payee of account number: $payeeAccount (of the bank: $payeeISPB) received BRL $amount from the $payerName of the account number: $payerAccount (of the bank: $payerISPB - $payerBankName) transaction at $finishedAt.\n";

        return $formattedMessage;
    }


    private function endMenuForUser(string $endToEndId): void
    {
        $this->userStatusService->clearUserStatus($endToEndId);
    }
}
