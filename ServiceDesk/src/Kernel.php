<?php
declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Делает поведение предсказуемым даже при частично настроенном проекте:
     * - аккуратно импортирует конфиги (yaml/php),
     * - подключает attribute-роуты из src/Controller,
     * - выставляет таймзону (через env APP_TIMEZONE или UTC).
     */
    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Таймзона: не падаем, если env нет — берём UTC.
        $tz = $_SERVER['APP_TIMEZONE'] ?? $_ENV['APP_TIMEZONE'] ?? 'UTC';
        if (\is_string($tz) && $tz !== '') {
            @date_default_timezone_set($tz);
        } else {
            @date_default_timezone_set('UTC');
        }

        $configDir = $this->getProjectDir().'/config';

        // Packages
        $container->import($configDir.'/{packages}/*.yaml');
        $container->import($configDir.'/{packages}/'.$this->environment.'/*.yaml');

        // Services (yaml/php)
        if (\is_file($configDir.'/services.yaml')) {
            $container->import($configDir.'/services.yaml');
            $container->import($configDir.'/{services}_'.$this->environment.'.yaml');
        } elseif (\is_file($configDir.'/services.php')) {
            $container->import($configDir.'/services.php');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir().'/config';

        // YAML routes (если есть)
        if (\is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        }
        if (\is_dir($configDir.'/routes')) {
            $routes->import($configDir.'/routes/{'.$this->environment.'}/*.yaml');
            $routes->import($configDir.'/routes/*.yaml');
        }

        // Attribute routes — “самое удобное” для демонстрации
        $controllerDir = $this->getProjectDir().'/src/Controller/';
        if (\is_dir($controllerDir)) {
            $routes->import($controllerDir, 'attribute');
        }
    }
}
