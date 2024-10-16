<?php

namespace App\Service;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Log\LoggerInterface;

class DynamoDbService
{
    private $client;
    private $logger;
    private $endpoint;

    private $aws_access_key;

    private $aws_secret_access;

    private $regiao;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->aws_access_key = $_ENV['AWS_ACCESS_KEY_ID'];

        $this->aws_secret_access = $_ENV['AWS_SECRET_ACCESS_KEY'];

        $this->regiao = $_ENV['REGION'];

        $this->endpoint = $_ENV['DYNAMODB_ENDPOINT'];

        
        $this->client = new DynamoDbClient([
            'region' => $this->regiao,
            'version' => 'latest',
            'endpoint' => $this->endpoint,
            'credentials' => [
                'key' => $this->aws_access_key,   
                'secret' => $this->aws_secret_access, 
            ],
        ]);

        $this->logger->info('DynamoDbService initialized with endpoint: ' . $this->endpoint);
    }

    public function getClient(): DynamoDbClient
    {
        return $this->client;
    }
}
