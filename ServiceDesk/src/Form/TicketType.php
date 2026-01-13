<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Ticket;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'attr' => ['maxlength' => 255, 'placeholder' => 'Коротко опиши проблему'],
                'constraints' => [
                    new NotBlank(message: 'Заголовок обязателен'),
                    new Length(min: 3, max: 255),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'attr' => ['rows' => 7, 'placeholder' => 'Подробно опиши проблему и шаги воспроизведения'],
                'constraints' => [
                    new NotBlank(message: 'Описание обязательно'),
                    new Length(min: 10, max: 20000),
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Приоритет',
                'choices' => [
                    'Низкий' => Ticket::PRIORITY_LOW,
                    'Средний' => Ticket::PRIORITY_MEDIUM,
                    'Высокий' => Ticket::PRIORITY_HIGH,
                ],
                'placeholder' => false,
                'constraints' => [
                    new Choice(choices: Ticket::allowedPriorities(), message: 'Некорректный приоритет'),
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Категория',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => '— выбери категорию —',
                'required' => false,
            ])
        ;
        // status/author/assignedTo тут не показываем — автор задаётся сервером, статус по умолчанию NEW
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
