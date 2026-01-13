<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/tickets')]
final class TicketController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('', name: 'ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketsRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $tickets = $ticketsRepo->findForUser($user, 200);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/new', name: 'ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('warning', 'Проверь поля формы — есть ошибки валидации.');
            } else {
                try {
                    $ticket->setAuthor($user);
                    // status/priority нормализуются в Entity, но не мешает оставить дефолты
                    if ($ticket->getStatus() === '') {
                        $ticket->setStatus(Ticket::STATUS_NEW);
                    }

                    $this->em->persist($ticket);
                    $this->em->flush();

                    $this->addFlash('success', 'Заявка создана.');
                    return $this->redirect('/tickets/'.$ticket->getId());
                } catch (\Throwable $e) {
                    $this->logger->error('Ticket create failed', ['exception' => $e]);
                    $this->addFlash('danger', 'Не удалось создать заявку (ошибка БД). Попробуй ещё раз.');
                }
            }
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'ticket_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Ticket $ticket): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Доступ: автор заявки ИЛИ SUPPORT/ADMIN
        $isOwner = $ticket->getAuthor() && $ticket->getAuthor()->getId() === $user->getId();
        $isStaff = $this->isGranted('ROLE_SUPPORT') || $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isStaff) {
            throw new AccessDeniedException('You cannot view this ticket.');
        }

        // Комментарий
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted()) {
            if (!$commentForm->isValid()) {
                $this->addFlash('warning', 'Комментарий не отправлен — проверь текст.');
            } else {
                try {
                    $comment->setTicket($ticket);
                    $comment->setAuthor($user);

                    $this->em->persist($comment);
                    $this->em->flush();

                    $this->addFlash('success', 'Комментарий добавлен.');
                    return $this->redirect('/tickets/'.$ticket->getId());
                } catch (\Throwable $e) {
                    $this->logger->error('Comment add failed', ['exception' => $e]);
                    $this->addFlash('danger', 'Не удалось добавить комментарий (ошибка БД).');
                }
            }
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'commentForm' => $commentForm->createView(),
        ]);
    }
}
