<?php

declare(strict_types=1);

namespace Jield\Search\Factory;

use Jield\Search\Service\ConsoleService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class ConsoleServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConsoleService
    {
        return new ConsoleService(container: $container);
    }
}
