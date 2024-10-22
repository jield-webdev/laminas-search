<?php

declare(strict_types=1);

namespace Jield\Search\Message\Handler;

use Doctrine\ORM\EntityManager;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Message\UpdateSearchEntityMessage;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final class UpdateSearchEntityHandler
{

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager
    )
    {
    }

    public function __invoke(UpdateSearchEntityMessage $updateSearchEntityMessage): int
    {
        $entityClassName = $updateSearchEntityMessage->getEntityClassName();
        $entityId        = $updateSearchEntityMessage->getEntityId();
        $searchServices  = $updateSearchEntityMessage->getSearchServices();

        //Clear the entity manager to always have fresh results
        $this->entityManager->clear();

        //Just use the entityManager to get the entity
        /** @var HasSearchInterface $entity */
        $entity = $this->entityManager->getRepository($entityClassName)->find($entityId);

        Assert::isInstanceOf($entity, class: HasSearchInterface::class);

        foreach ($searchServices as $searchService) {
            //I have to tell the system which search service we have to take (to have the correct connection)
            /** @var AbstractSearchService $searchServiceInstance */
            $searchServiceInstance = $this->container->get($searchService);
            $searchServiceInstance->updateEntity($entity);
        }

        return 0;
    }
}
