<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый контроллер с безопасными ответами и единым поведением при ошибках.
 */
abstract class BaseController extends AbstractController
{
    protected function jsonOk(array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'data' => $data,
        ], $status, $headers);
    }

    protected function jsonFail(
        string $message = 'Unexpected error',
        int $status = 500,
        array $details = [],
        ?\Throwable $e = null,
        ?LoggerInterface $logger = null
    ): JsonResponse {
        // В проде не светим внутренности
        $debug = false;
        try {
            $debug = (bool) $this->getParameter('kernel.debug');
        } catch (\Throwable) {
            $debug = false;
        }

        if ($e && $logger) {
            $logger->error($message, [
                'exception' => $e,
                'details' => $details,
            ]);
        }

        $payload = [
            'ok' => false,
            'error' => [
                'message' => $message,
                'details' => $details,
            ],
        ];

        if ($debug && $e) {
            $payload['error']['debug'] = [
                'type' => $e::class,
                'message' => $e->getMessage(),
            ];
        }

        return $this->json($payload, $status);
    }

    /**
     * Fallback-ответ HTML, если шаблон/твиг ещё не готов или упал рендер.
     */
    protected function htmlFallback(string $title, string $body, int $status = 200): Response
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeBody  = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return new Response(
            "<!doctype html><html><head><meta charset='utf-8'><title>{$safeTitle}</title></head><body><h1>{$safeTitle}</h1><p>{$safeBody}</p></body></html>",
            $status,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
