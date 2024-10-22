<?php

declare(strict_types=1);

namespace Jield\Search\Message\Handler;

use Doctrine\ORM\EntityManager;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final class UpdateSearchEntitiesHandler
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager,
    )
    {
    }

    public function __invoke(UpdateSearchEntitiesMessage $updateSearchEntitiesMessage): int
    {
        $entityClassName = $updateSearchEntitiesMessage->getEntityClassName();
        $entityIds       = $updateSearchEntitiesMessage->getEntityIds();
        $searchService   = $updateSearchEntitiesMessage->getSearchServices();

        //Clear the entity manager to always have fresh results
        $this->entityManager->clear();

        //Grab the entities
        $entities = [];

        foreach ($entityIds as $entityId) {

            $entity = $this->entityManager->getRepository($entityClassName)->find($entityId);

            Assert::isInstanceOf($entity, class: HasSearchInterface::class);

            //We already know that the entities are all the same type
            $entities[] = $entity;
        }

        foreach ($searchServices as $searchService) {
            //I have to tell the system which search service we have to take (to have the correct connection)
            /** @var AbstractSearchService $searchServiceInstance */
            $searchServiceInstance = $this->container->get($searchService);
            $searchServiceInstance->updateEntities($entities);
        }

        return null;
    }
}
