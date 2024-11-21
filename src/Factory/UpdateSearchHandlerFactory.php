<?php

declare(strict_types=1);

namespace Jield\Search\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class UpdateSearchHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new $requestedName(
            container: $container,
            entityManager: $container->get(EntityManager::class),
        );
    }
}
