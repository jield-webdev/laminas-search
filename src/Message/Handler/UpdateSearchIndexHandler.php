<?php

declare(strict_types=1);

namespace Jield\Search\Message\Handler;

use Doctrine\ORM\EntityManager;
use Jield\Search\Message\UpdateSearchIndexMessage;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateSearchIndexHandler
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager
    )
    {
    }

    public function __invoke(UpdateSearchIndexMessage $updateSearchIndexMessage): int
    {
        $entityClassName = $updateSearchIndexMessage->getEntityClassName();
        $searchServices  = $updateSearchIndexMessage->getSearchServices();

        //Clear the entity manager to always have fresh results
        $this->entityManager->clear();

        $output = new BufferedOutput();

        foreach ($searchServices as $searchService) {
            //I have to tell the system which search service we have to take (to have the correct connection)
            /** @var AbstractSearchService $searchServiceInstance */
            $searchServiceInstance = $this->container->get($searchService);
            $searchServiceInstance->updateCollection(output: $output, entity: new $entityClassName());
        }

        return 0;
    }
}
