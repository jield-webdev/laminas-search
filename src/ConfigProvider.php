<?php

namespace Jield\Search;

use Jield\Search\Command\ListCores;
use Jield\Search\Command\SyncIndex;
use Jield\Search\Command\TestIndex;
use Jield\Search\Command\UpdateIndex;
use Jield\Search\Controller\Plugin\GetFilter;
use Jield\Search\Factory\ConsoleServiceFactory;
use Jield\Search\Factory\GetFilterFactory;
use Jield\Search\Factory\SearchQueueServiceFactory;
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
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageHandlerMiddlewareStaticFactory;
use Netglue\PsrContainer\Messenger\Container\Middleware\MessageSenderMiddlewareStaticFactory;
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
                UpdateSearchEntityHandler:: class     => UpdateSearchHandlerFactory::class,
                UpdateSearchEntitiesHandler:: class   => UpdateSearchHandlerFactory::class,
                UpdateSearchIndexHandler:: class      => UpdateSearchHandlerFactory::class,
                UpdateIndex::class                    => ConfigAbstractFactory::class,
                SyncIndex::class                      => ConfigAbstractFactory::class,
                TestIndex::class                      => ConfigAbstractFactory::class,
                ListCores::class                      => ConfigAbstractFactory::class,
                SearchQueueService::class             => SearchQueueServiceFactory::class,
                ConsoleService::class                 => ConsoleServiceFactory::class,
                'Jield\Search\Bus'                    => [MessageBusStaticFactory::class, 'Jield\Search\Bus'],
                'Jield\Search\Bus\Sender\Middleware'  => [MessageSenderMiddlewareStaticFactory::class, 'Jield\Search\Bus'],
                'Jield\Search\Bus\Handler\Middleware' => [MessageHandlerMiddlewareStaticFactory::class, 'Jield\Search\Bus'],
                'my.redis.transport'                  => [\Netglue\PsrContainer\Messenger\Container\TransportFactory::class, 'my.redis.transport'],
                'my_default_failure_transport'        => [\Netglue\PsrContainer\Messenger\Container\TransportFactory::class, 'my.redis.transport'],
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
                    // Doctrine…
                    // @link https://symfony.com/doc/current/messenger.html#doctrine-transport
                    'my.doctrine.transport'  => [
                        'dsn'        => 'doctrine://default',
                        'options'    => [],
                        'serializer' => SymfonySerializer::class,
                    ],

                    // In Memory for Testing
                    'my.in-memory.transport' => [
                        'dsn'        => 'in-memory://',
                        'options'    => [],
                        'serializer' => SymfonySerializer::class,
                    ],

                    // Redis
                    // @link https://symfony.com/doc/current/messenger.html#redis-transport
                    'my.redis.transport'     => [
                        'dsn'        => 'redis://redis-itea:6379/messages',
                        'options'    => [], // Redis specific options
                        'serializer' => SymfonySerializer::class,
                    ],
                ],
                'buses'             => [
                    'Jield\Search\Bus' => [
                        'allows_zero_handlers' => false, // Means that it's an error if no handlers are defined for a given message

                        /**
                         * Each bus needs middleware to do anything useful.
                         *
                         * Below is a minimal configuration to handle messages
                         */
                        'middleware'           => [
                            // … Middleware that inspects the message before it has been sent to a transport would go here.
                            'Jield\Search\Bus\Sender\Middleware', // Sends messages via a transport if configured.
                            'Jield\Search\Bus\Handler\Middleware', // Executes the handlers configured for the message
                        ],

                        /**
                         * Map messages to one or more handlers:
                         *
                         * Two locators are shipped, 1 message type to 1 handler and 1 message type to many handlers.
                         * Both locators operate on the basis that handlers are available in the container.
                         *
                         */
                        'handler_locator'      => \Netglue\PsrContainer\Messenger\HandlerLocator\OneToOneFqcnContainerHandlerLocator::class,
                        'handlers'             => [
                            // Example using OneToManyFqcnContainerHandlerLocator:
                            // \My\Event\SomethingHappened::class => [\My\ReactOnce::class, \My\ReactTwice::class],

                            // Example using OneToOneFqcnContainerHandlerLocator
                            UpdateSearchEntitiesMessage::class => UpdateSearchEntitiesHandler::class,
                            UpdateSearchEntityMessage::class   => UpdateSearchEntityHandler::class,
                            UpdateSearchIndexMessage::class    => UpdateSearchIndexHandler::class,
                        ],

                        //                    'logger' => 'MyLogger2', // Optional, but useful for debugging

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
                            UpdateSearchEntitiesMessage::class => ['my.redis.transport'],
                            UpdateSearchEntityMessage::class   => ['my.redis.transport'],
                            UpdateSearchIndexMessage::class    => ['my.redis.transport'],
                        ],
                    ],
                ],

            ],
        ];
    }
}
