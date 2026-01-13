<?php
declare(strict_types=1);

namespace App\Controller\Admin;

if (\class_exists(\EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class)) {

    final class UserCrudController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return \App\Entity\User::class;
        }

        public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
        {
            return $crud
                ->setEntityLabelInSingular('User')
                ->setEntityLabelInPlural('Users')
                ->setDefaultSort(['id' => 'DESC']);
        }

        public function configureFields(string $pageName): iterable
        {
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\IdField::new('id')->onlyOnIndex();
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\EmailField::new('email');
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField::new('roles')->onlyOnForms();

            if (\property_exists(\App\Entity\User::class, 'createdAt')) {
                yield \EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField::new('createdAt')->onlyOnIndex();
            }
        }
    }

} else {
    final class UserCrudController {}
}
