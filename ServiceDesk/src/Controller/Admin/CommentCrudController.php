<?php
declare(strict_types=1);

namespace App\Controller\Admin;

if (\class_exists(\EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class)) {

    final class CommentCrudController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return \App\Entity\Comment::class;
        }

        public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
        {
            return $crud
                ->setEntityLabelInSingular('Comment')
                ->setEntityLabelInPlural('Comments')
                ->setDefaultSort(['id' => 'DESC']);
        }

        public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
        {
            return $actions->disable(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DELETE);
        }

        public function configureFields(string $pageName): iterable
        {
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\IdField::new('id')->onlyOnIndex();

            if (\property_exists(\App\Entity\Comment::class, 'ticket')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('ticket');
            }
            if (\property_exists(\App\Entity\Comment::class, 'author')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('author');
            }

            yield \EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField::new('message')->onlyOnForms();
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\TextField::new('message')->onlyOnIndex()->setMaxLength(80);
        }
    }

} else {
    final class CommentCrudController {}
}
