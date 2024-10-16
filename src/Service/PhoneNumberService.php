<?php

// src/Service/PhoneNumberService.php
namespace App\Service;

class PhoneNumberService
{
    public function formatRemoteJid(string $remoteJid): string
    {
        $formattedRemoteJid = str_replace('@s.whatsapp.net', '', $remoteJid);

        if (strlen($remoteJid) >= 4) {
            // Adiciona '9' após os primeiros 4 caracteres
            $formattedJid = substr($remoteJid, 0, 4) . '9' . substr($remoteJid, 4);
            return $formattedJid;
        }
        return $remoteJid;
    }
}