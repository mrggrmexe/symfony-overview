<?php
declare(strict_types=1);

namespace App\Controller\Admin;

if (\class_exists(\EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController::class)) {

    final class CategoryCrudController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
    {
        public static function getEntityFqcn(): string
        {
            return \App\Entity\Category::class;
        }

        public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
        {
            return $crud
                ->setEntityLabelInSingular('Category')
                ->setEntityLabelInPlural('Categories')
                ->setDefaultSort(['id' => 'ASC']);
        }

        public function configureFields(string $pageName): iterable
        {
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\IdField::new('id')->onlyOnIndex();
            yield \EasyCorp\Bundle\EasyAdminBundle\Field\TextField::new('name');
        }
    }

} else {
    final class CategoryCrudController {}
}
