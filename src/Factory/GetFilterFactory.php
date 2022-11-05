<?php

declare(strict_types=1);

namespace Jield\Search\Factory;

use Jield\Search\Controller\Plugin\GetFilter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class GetFilterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GetFilter
    {
        return new GetFilter(container: $container);
    }
}
