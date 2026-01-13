<?php
declare(strict_types=1);

namespace App\Controller\Admin;

if (\class_exists(\EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class)) {

    final class TicketCrudController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return \App\Entity\Ticket::class;
        }

        public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
        {
            return $crud
                ->setEntityLabelInSingular('Ticket')
                ->setEntityLabelInPlural('Tickets')
                ->setSearchFields(['id', 'title', 'status'])
                ->setDefaultSort(['id' => 'DESC']);
        }

        public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
        {
            return $actions->disable(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DELETE);
        }

        public function configureFields(string $pageName): iterable
        {
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\IdField::new('id')->onlyOnIndex();
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\TextField::new('title');
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField::new('description')->onlyOnForms();

            yield \EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField::new('status')->setChoices([
                'NEW' => 'NEW',
                'IN_PROGRESS' => 'IN_PROGRESS',
                'RESOLVED' => 'RESOLVED',
                'CLOSED' => 'CLOSED',
            ]);

            if (\property_exists(\App\Entity\Ticket::class, 'priority')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField::new('priority')->setChoices([
                    'LOW' => 'LOW',
                    'MEDIUM' => 'MEDIUM',
                    'HIGH' => 'HIGH',
                ]);
            }

            if (\property_exists(\App\Entity\Ticket::class, 'category')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('category');
            }
            if (\property_exists(\App\Entity\Ticket::class, 'author')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('author')->onlyOnIndex();
            }
            if (\property_exists(\App\Entity\Ticket::class, 'assignedTo')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('assignedTo');
            }
        }
    }

} else {
    final class TicketCrudController {}
}
