<?php

declare(strict_types=1);

namespace Jield\Search\Factory;

use Jield\Search\Service\SearchQueueService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueue\Queue\QueuePluginManager;

final class SearchQueueServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SearchQueueService
    {
        return new SearchQueueService(
            container: $container,
            cores: $container->get('config')['search']['cores'] ?? [],
            jobPluginManager: $container->get(JobPluginManager::class),
            queuePluginManager: $container->get(QueuePluginManager::class)
        );
    }
}
