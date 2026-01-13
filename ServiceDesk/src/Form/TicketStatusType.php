<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

final class TicketStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Статус',
                'choices' => [
                    'NEW' => Ticket::STATUS_NEW,
                    'IN_PROGRESS' => Ticket::STATUS_IN_PROGRESS,
                    'RESOLVED' => Ticket::STATUS_RESOLVED,
                    'CLOSED' => Ticket::STATUS_CLOSED,
                ],
                'constraints' => [
                    new Choice(choices: Ticket::allowedStatuses(), message: 'Некорректный статус'),
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Приоритет',
                'choices' => [
                    'LOW' => Ticket::PRIORITY_LOW,
                    'MEDIUM' => Ticket::PRIORITY_MEDIUM,
                    'HIGH' => Ticket::PRIORITY_HIGH,
                ],
                'constraints' => [
                    new Choice(choices: Ticket::allowedPriorities(), message: 'Некорректный приоритет'),
                ],
            ])
            ->add('assignedTo', EntityType::class, [
                'label' => 'Назначить на',
                'class' => User::class,
                'choice_label' => 'email',
                'placeholder' => '— не назначено —',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
