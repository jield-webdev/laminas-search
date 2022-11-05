<?php

namespace Jield\Search;

use Jield\Search\Command\ListCores;
use Jield\Search\Command\SyncIndex;
use Jield\Search\Command\TestIndex;
use Jield\Search\Command\UpdateIndex;
use Jield\Search\Factory\ConsoleServiceFactory;
use Jield\Search\Service\ConsoleService;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigAbstractFactory::class => $this->getConfigAbstractFactory(),
            'service_manager' => $this->getServiceMangerConfig(),
            'laminas-cli' => $this->getCommandConfig(),
            'view_manager' => $this->getViewManagerConfig(),
        ];
    }

    public function getViewManagerConfig(): array
    {
        return [
            'template_map' => [
                'search/partial/show-filter' => __DIR__ . '/../view/search/partial/show-filter.twig',
            ]
        ];
    }

    public function getCommandConfig(): array
    {
        return [
            'commands' => [
                'search:update-index' => UpdateIndex::class,
                'search:sync-index' => SyncIndex::class,
                'search:test-index' => TestIndex::class,
                'search:list-cores' => ListCores::class,
            ]
        ];
    }

    public function getServiceMangerConfig(): array
    {
        return [
            'factories' => [
                UpdateIndex::class => ConfigAbstractFactory::class,
                SyncIndex::class => ConfigAbstractFactory::class,
                TestIndex::class => ConfigAbstractFactory::class,
                ListCores::class => ConfigAbstractFactory::class,
                ConsoleService::class => ConsoleServiceFactory::class,
            ],
        ];
    }

    public function getConfigAbstractFactory(): array
    {
        return [
            UpdateIndex::class => [
                ConsoleService::class,
            ],
            SyncIndex::class => [
                ConsoleService::class,
            ],
            TestIndex::class => [
                ConsoleService::class,
            ],
            ListCores::class => [
                ConsoleService::class,
            ],

        ];
    }
}
