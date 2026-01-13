<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends BaseController
{
    #[Route('/healthz', name: 'healthz', methods: ['GET'])]
    public function healthz()
    {
        // Быстрый “пинг” что сервер жив и роутинг работает
        return $this->jsonOk([
            'service' => 'ServiceDesk',
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}
