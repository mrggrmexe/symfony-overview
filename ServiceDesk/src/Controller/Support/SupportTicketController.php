<?php
declare(strict_types=1);

namespace App\Controller\Support;

use App\Controller\BaseController;
use App\Entity\Ticket;
use App\Form\TicketStatusType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/support')]
final class SupportTicketController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    private function denySupportAccess(): void
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // “OR” без настройки role_hierarchy — делаем вручную
        if (!$this->isGranted('ROLE_SUPPORT') && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Support access required.');
        }
    }

    private function qInt(Request $request, string $key, ?int $default = null): ?int
    {
        $v = $request->query->get($key);
        if ($v === null || $v === '') return $default;
        $i = filter_var($v, FILTER_VALIDATE_INT);
        return ($i === false) ? $default : $i;
    }

    #[Route('/tickets', name: 'support_tickets', methods: ['GET'])]
    public function index(Request $request, TicketRepository $repo): Response
    {
        $this->denySupportAccess();

        $q = (string) $request->query->get('q', '');
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        $page = $this->qInt($request, 'page', 1) ?? 1;
        $perPage = $this->qInt($request, 'perPage', 20) ?? 20;
        $categoryId = $this->qInt($request, 'categoryId', null);
        $authorId = $this->qInt($request, 'authorId', null);
        $assignedToId = $this->qInt($request, 'assignedToId', null);

        $res = $repo->search(
            q: $q,
            status: is_string($status) ? $status : null,
            priority: is_string($priority) ? $priority : null,
            categoryId: $categoryId,
            authorId: $authorId,
            assignedToId: $assignedToId,
            page: $page,
            perPage: $perPage
        );

        // Шаблон сейчас ждёт tickets — отдаём только items, чтобы всё совпало
        return $this->render('support/index.html.twig', [
            'tickets' => $res['items'],
            // если захочешь — можно добавить пагинацию: total/page/perPage
            'meta' => $res,
        ]);
    }

    #[Route('/tickets/{id<\d+>}', name: 'support_ticket_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Ticket $ticket): Response
    {
        $this->denySupportAccess();

        $form = $this->createForm(TicketStatusType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('warning', 'Форма не сохранена — проверь значения.');
            } else {
                try {
                    $this->em->flush();
                    $this->addFlash('success', 'Изменения сохранены.');
                    return $this->redirect('/support/tickets/'.$ticket->getId());
                } catch (\Throwable $e) {
                    $this->logger->error('Support update ticket failed', ['exception' => $e]);
                    $this->addFlash('danger', 'Не удалось сохранить изменения (ошибка БД).');
                }
            }
        }

        return $this->render('support/show.html.twig', [
            'ticket' => $ticket,
            'statusForm' => $form->createView(),
        ]);
    }
}
