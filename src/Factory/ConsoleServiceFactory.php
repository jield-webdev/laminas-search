<?php

declare(strict_types=1);

namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Jield\Search\Service\ConsoleService;

final class ConsoleServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ConsoleService
    {
        return new ConsoleService(container: $container);
    }
}
