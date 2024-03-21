<?php

declare(strict_types=1);

namespace Jield\Search\Factory;

use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\AbstractJob;

final class JobFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractJob
    {
        return new $requestedName(container: $container, entityManager: $container->get(EntityManager::class));
    }
}
