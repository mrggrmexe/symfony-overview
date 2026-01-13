<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends BaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        // Если уже залогинен — смысла регистрироваться нет
        if ($this->getUser()) {
            $this->addFlash('info', 'Ты уже авторизован.');
            return $this->redirect('/');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $this->addFlash('warning', 'Проверь форму: есть ошибки.');
            } else {
                try {
                    // защита от дублей email (даже если есть unique constraint в БД)
                    $email = $user->getEmail();
                    if ($email === '' || $this->users->findOneByEmail($email)) {
                        $this->addFlash('danger', 'Пользователь с таким email уже существует.');
                        return $this->render('security/register.html.twig', [
                            'registrationForm' => $form->createView(),
                        ]);
                    }

                    /** @var string $plainPassword */
                    $plainPassword = (string) $form->get('plainPassword')->getData();
                    $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

                    // На регистрации всегда ROLE_USER (поддержку/админа делай через fixtures/админку)
                    $user->setRoles(['ROLE_USER']);

                    $this->em->persist($user);
                    $this->em->flush();

                    $this->addFlash('success', 'Регистрация успешна! Теперь можно войти.');
                    return $this->redirect('/login');
                } catch (\Throwable $e) {
                    $this->logger->error('Registration failed', ['exception' => $e]);
                    $this->addFlash('danger', 'Не удалось зарегистрироваться (ошибка сервера/БД).');
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
