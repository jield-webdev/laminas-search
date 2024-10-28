<?php

namespace Jield\Search;

use Jield\Search\Command\ListCores;
use Jield\Search\Command\SyncIndex;
use Jield\Search\Command\TestIndex;
use Jield\Search\Command\UpdateIndex;
use Jield\Search\Controller\Plugin\GetFilter;
use Jield\Search\Factory\ConsoleServiceFactory;
use Jield\Search\Factory\GetFilterFactory;
use Jield\Search\Factory\SearchUpdateServiceFactory;
use Jield\Search\Factory\UpdateSearchHandlerFactory;
use Jield\Search\Message\Handler\UpdateSearchEntitiesHandler;
use Jield\Search\Message\Handler\UpdateSearchEntityHandler;
use Jield\Search\Message\Handler\UpdateSearchIndexHandler;
use Jield\Search\Message\UpdateSearchEntitiesMessage;
use Jield\Search\Message\UpdateSearchEntityMessage;
use Jield\Search\Message\UpdateSearchIndexMessage;
use Jield\Search\Service\ConsoleService;
use Jield\Search\Service\SearchUpdateService;
use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Netglue\PsrContainer\Messenger\Container\MessageBusStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\BusNameStampMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageHandlerMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageSenderMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\TransportFactory;
use Netglue\PsrContainer\Messenger\HandlerLocator\OneToOneFqcnContainerHandlerLocator;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as SymfonySerializer;

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
            'symfony'                    => $this->getSymfonyConfig(),
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
                UpdateSearchEntityHandler:: class             => UpdateSearchHandlerFactory::class,
                UpdateSearchEntitiesHandler:: class           => UpdateSearchHandlerFactory::class,
                UpdateSearchIndexHandler:: class              => UpdateSearchHandlerFactory::class,
                UpdateIndex::class                            => ConfigAbstractFactory::class,
                SyncIndex::class                              => ConfigAbstractFactory::class,
                TestIndex::class                              => ConfigAbstractFactory::class,
                ListCores::class                              => ConfigAbstractFactory::class,
                SearchUpdateService::class                    => SearchUpdateServiceFactory::class,
                ConsoleService::class                         => ConsoleServiceFactory::class,
                'Jield\Search\Command\Bus'                    => [MessageBusStaticFactory::class, 'Jield\Search\Command\Bus'],
                'Jield\Search\Command\Bus\Name\Middleware'    => [BusNameStampMiddlewareStaticFactory::class, 'Jield\Search\Command\Bus'],
                'Jield\Search\Command\Bus\Sender\Middleware'  => [MessageSenderMiddlewareStaticFactory::class, 'Jield\Search\Command\Bus'],
                'Jield\Search\Command\Bus\Handler\Middleware' => [MessageHandlerMiddlewareStaticFactory::class, 'Jield\Search\Command\Bus'],
                'Jield\Search\Transport\Doctrine'             => [TransportFactory::class, 'Jield\Search\Transport\Doctrine'],
                'my_default_failure_transport'                => [TransportFactory::class, 'my_default_failure_transport'],
                'command_failures'                            => [TransportFactory::class, 'command_failures'],
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

    private function getSymfonyConfig(): array
    {
        return [
            'messenger' => [
                'failure_transport' => 'my_default_failure_transport',
                'transports'        => [
                    'Jield\Search\Transport\Doctrine' => [
                        'dsn'        => 'doctrine://doctrine.entitymanager.orm_default',
                        'options'    => [
                            'table_name' => 'admin_messages',
                            'queue_name' => 'Jield\Search\Transport\Doctrine',
                        ],
                        'serializer' => SymfonySerializer::class,
                    ],
                    'my_default_failure_transport'    => [
                        'dsn' => 'in-memory:///',
                    ],
                ],
                'buses'             => [
                    'Jield\Search\Command\Bus' => [
                        'allows_zero_handlers' => false, // Means that it's an error if no handlers are defined for a given message

                        /**
                         * Each bus needs middleware to do anything useful.
                         *
                         * Below is a minimal configuration to handle messages
                         */
                        'middleware'           => [
                            'Jield\Search\Command\Bus\Name\Middleware', // Add the name to the envelope (make sure this is the first middleware)
                            'Jield\Search\Command\Bus\Sender\Middleware', // Sends messages via a transport if configured.
                            'Jield\Search\Command\Bus\Handler\Middleware', // Executes the handlers configured for the message
                        ],

                        /**
                         * Map messages to one or more handlers:
                         *
                         * Two locators are shipped, 1 message type to 1 handler and 1 message type to many handlers.
                         * Both locators operate on the basis that handlers are available in the container.
                         *
                         */
                        'handler_locator'      => OneToOneFqcnContainerHandlerLocator::class,
                        'handlers'             => [
                            UpdateSearchEntitiesMessage::class => UpdateSearchEntitiesHandler::class,
                            UpdateSearchEntityMessage::class   => UpdateSearchEntityHandler::class,
                            UpdateSearchIndexMessage::class    => UpdateSearchIndexHandler::class,
                        ],

                        /**
                         * Routes define which transport(s) that messages dispatched on this bus should be sent with.
                         *
                         * The * wildcard applies to all messages.
                         * The transport for each route must be an array of one or more transport identifiers. Each transport
                         * is retrieved from the DI container by this value.
                         *
                         * An empty routes definition would mean that messages would be handled immediately and synchronously,
                         * i.e. no transport would be used.
                         *
                         * Route specific messages to specific transports by using the message name as the key.
                         */
                        'routes'               => [
                            UpdateSearchEntitiesMessage::class => ['Jield\Search\Transport\Doctrine'],
                            UpdateSearchEntityMessage::class   => ['Jield\Search\Transport\Doctrine'],
                            UpdateSearchIndexMessage::class    => ['Jield\Search\Transport\Doctrine'],
                        ],
                    ],
                ],

            ],
        ];
    }
}
