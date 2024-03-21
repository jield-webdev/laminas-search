<?php

namespace Jield\Search;

use Jield\Search\Command\ListCores;
use Jield\Search\Command\SyncIndex;
use Jield\Search\Command\TestIndex;
use Jield\Search\Command\UpdateIndex;
use Jield\Search\Controller\Plugin\GetFilter;
use Jield\Search\Factory\ConsoleServiceFactory;
use Jield\Search\Factory\GetFilterFactory;
use Jield\Search\Factory\JobFactory;
use Jield\Search\Factory\SearchQueueServiceFactory;
use Jield\Search\Job\UpdateSearchEntities;
use Jield\Search\Job\UpdateSearchEntity;
use Jield\Search\Job\UpdateSearchIndex;
use Jield\Search\Service\ConsoleService;
use Jield\Search\Service\SearchQueueService;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use SlmQueue\Strategy\MaxMemoryStrategy;
use SlmQueue\Strategy\WorkerLifetimeStrategy;
use SlmQueueDoctrine\Factory\DoctrineQueueFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigAbstractFactory::class => $this->getConfigAbstractFactory(),
            'service_manager'            => $this->getServiceMangerConfig(),
            'laminas-cli'                => $this->getCommandConfig(),
            'view_manager'               => $this->getViewManagerConfig(),
            'controller_plugins'         => $this->getControllerPluginConfig(),
            'slm_queue'                  => $this->getQueueConfig(),
        ];
    }

    public function getControllerPluginConfig(): array
    {
        return [
            'aliases'   => [
                'getFilter' => GetFilter::class,
            ],
            'factories' => [
                GetFilter::class => GetFilterFactory::class,
            ],
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
                'search:sync-index'   => SyncIndex::class,
                'search:test-index'   => TestIndex::class,
                'search:list-cores'   => ListCores::class,
            ]
        ];
    }

    public function getServiceMangerConfig(): array
    {
        return [
            'factories' => [
                UpdateIndex::class        => ConfigAbstractFactory::class,
                SyncIndex::class          => ConfigAbstractFactory::class,
                TestIndex::class          => ConfigAbstractFactory::class,
                ListCores::class          => ConfigAbstractFactory::class,
                ConsoleService::class     => ConsoleServiceFactory::class,
                SearchQueueService::class => SearchQueueServiceFactory::class,
            ],
        ];
    }

    public function getQueueConfig(): array
    {
        return [
            'queues'            => [
                'search' => [
                    'table_name'       => 'queue_default',
                    'buried_lifetime'  => -1,
                    'deleted_lifetime' => 60 * 24 * 2 #in minutes,
                ],
            ],
            'worker_strategies' => [
                'default' => [ // per worker
                               WorkerLifetimeStrategy::class => ['lifetime' => 299],
                               MaxMemoryStrategy::class      => ['max_memory' => 1000 * 1024 * 1024],
                ],
                'queues'  => [ // per queue
                               'default' => [],
                ],
            ],
            'strategy_manager'  => [],
            'queue_manager'     => [
                'factories' => [
                    'search' => DoctrineQueueFactory::class,
                ],
            ],
            'job_manager'       => [
                'factories' => [
                    UpdateSearchEntity::class   => JobFactory::class,
                    UpdateSearchEntities::class => JobFactory::class,
                    UpdateSearchIndex::class    => JobFactory::class,
                ],
            ],
        ];
    }

    public function getConfigAbstractFactory(): array
    {
        return [
            UpdateIndex::class => [
                ConsoleService::class,
            ],
            SyncIndex::class   => [
                ConsoleService::class,
            ],
            TestIndex::class   => [
                ConsoleService::class,
            ],
            ListCores::class   => [
                ConsoleService::class,
            ],
        ];
    }
}
