<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
class UserStatusService
{
    private $statusFile;
    private $evolutionApiService; 
    public function __construct(EvolutionApiService $evolutionApiService)
    {
        $this->statusFile = __DIR__ . '/../../public/user_status.json'; 
        $this->evolutionApiService = $evolutionApiService; 
    }

    public function getUserStatus(string $remoteJid): ?array
    {
        $this->logMessage('Getting status for user: ' . $remoteJid);
        
        if (!file_exists($this->statusFile)) {
            $this->logMessage('Status file does not exist');
            return null;
        }

        $statuses = json_decode(file_get_contents($this->statusFile), true);

        return $statuses[$remoteJid] ?? null;
    }

    public function setUserStatus(string $remoteJid, string $status): void
    {
        $this->logMessage('Setting status for user: ' . $remoteJid . ' to ' . $status);
        
        $statuses = [];
        if (file_exists($this->statusFile)) {
            $statuses = json_decode(file_get_contents($this->statusFile), true);
        }

        $statuses[$remoteJid] = [
            'status' => $status,
            'timestamp' => time()
        ];

        file_put_contents($this->statusFile, json_encode($statuses));
    }

    public function isMenuExpired(string $remoteJid): bool
    {
        $status = $this->getUserStatus($remoteJid);

        if (!$status || $status['status'] !== 'menu_sent') {
            return true; 
        }

        $this->logMessage('Checking if menu is expired for user: ' . $remoteJid);
        $currentTime = time();
        $elapsedTime = $currentTime - $status['timestamp'];

        $menuExpirationTime = 300; 
        $isExpired = $elapsedTime > $menuExpirationTime;


        $this->logMessage('Menu expired for user: ' . $remoteJid . ' - ' . ($isExpired ? 'Yes' : 'No'));

        return $isExpired;
    }

    public function clearUserStatus(string $remoteJid): void
    {
        $this->logMessage('Clearing status for user: ' . $remoteJid);
        
        if (!file_exists($this->statusFile)) {
            $this->logMessage('Status file does not exist');
            return;
        }

        $statuses = json_decode(file_get_contents($this->statusFile), true);
        
        if (isset($statuses[$remoteJid])) {
            unset($statuses[$remoteJid]);
            file_put_contents($this->statusFile, json_encode($statuses));
        }
    }

    private function logMessage(string $message): void
    {
        $logFile = __DIR__ . '/../../public/user_status.log'; 
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    }
    
   
}
