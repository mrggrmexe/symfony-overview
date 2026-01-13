<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

if (\class_exists(\EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController::class)) {

    final class DashboardController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController
    {
        public function __construct(private readonly LoggerInterface $logger) {}

        #[Route('/admin', name: 'admin', methods: ['GET'])]
        #[IsGranted('ROLE_ADMIN')]
        public function index(): Response
        {
            try {
                // Можно редиректить на нужный CRUD по умолчанию:
                // $url = $this->container->get(\EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator::class)
                //    ->setController(TicketCrudController::class)
                //    ->generateUrl();
                // return $this->redirect($url);

                return parent::index();
            } catch (\Throwable $e) {
                $this->logger->error('EasyAdmin dashboard failed.', ['exception' => $e]);

                // Fallback (чтобы на защите не “краснело”)
                return new Response(
                    '<h1>Admin</h1><p>Админка упала. Проверь логи и конфигурацию EasyAdmin.</p>',
                    500,
                    ['Content-Type' => 'text/html; charset=UTF-8']
                );
            }
        }

        public function configureDashboard(): \EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard
        {
            try {
                return \EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard::new()
                    ->setTitle('ServiceDesk • Admin')
                    ->renderContentMaximized();
            } catch (\Throwable $e) {
                $this->logger->error('configureDashboard failed.', ['exception' => $e]);
                return \EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard::new()->setTitle('ServiceDesk • Admin');
            }
        }

        public function configureMenuItems(): iterable
        {
            // Меню делаем “безопасным”: если сущностей ещё нет — будет ошибка.
            // Поэтому: проверяем наличие классов перед добавлением пунктов.
            yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

            if (\class_exists(\App\Entity\Ticket::class)) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Tickets', 'fa fa-ticket', \App\Entity\Ticket::class);
            }
            if (\class_exists(\App\Entity\Category::class)) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Categories', 'fa fa-tags', \App\Entity\Category::class);
            }
            if (\class_exists(\App\Entity\Comment::class)) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Comments', 'fa fa-comments', \App\Entity\Comment::class);
            }
            if (\class_exists(\App\Entity\User::class)) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Users', 'fa fa-user', \App\Entity\User::class);
            }

            yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::section('System');
            yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToUrl('Healthz', 'fa fa-heartbeat', '/healthz');
            yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToUrl('Home', 'fa fa-arrow-left', '/');
        }
    }

} else {

    // Fallback-версия: НЕ требует EasyAdmin и не ломает проект.
    final class DashboardController extends BaseController
    {
        public function __construct(private readonly LoggerInterface $logger) {}

        #[Route('/admin', name: 'admin', methods: ['GET'])]
        #[IsGranted('ROLE_ADMIN')]
        public function index(): Response
        {
            $this->logger->warning('EasyAdmin is not installed; /admin fallback used.');

            return $this->htmlFallback(
                'Admin (fallback)',
                'EasyAdmin не установлен. Установи: composer require easycorp/easyadmin-bundle',
                200
            );
        }
    }
}
