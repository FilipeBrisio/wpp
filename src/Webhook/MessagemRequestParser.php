<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;
use Symfony\Component\HttpFoundation\Exception\JsonException;

final class MessagemRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new PathRequestMatcher('/webhook/mensagem');
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        // $authToken = $request->headers->get('X-Authentication-Token');
        
        // if ($authToken !== $secret) {
        //     throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Token de autenticação inválido.');
        // }

        $content = $request->getContent();
        $payload = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'JSON inválido.');
        }

        if (!isset($payload['message']) || !isset($payload['from'])) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Payload não contém campos necessários.');
        }

        return new RemoteEvent(
            'mensagem', // Tipo de evento remoto
            $payload['from'], // Remetente da mensagem
            $payload // Payload completo da mensagem
        );
    }
}
