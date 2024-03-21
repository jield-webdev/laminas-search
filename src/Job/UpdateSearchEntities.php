<?php

declare(strict_types=1);

namespace Jield\Search\Job;

use Doctrine\ORM\EntityManager;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\AbstractJob;
use Webmozart\Assert\Assert;

final class UpdateSearchEntities extends AbstractJob
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager,
    )
    {
    }

    public function execute(): ?int
    {
        $entityClassName = $this->getContent()['entityClassName'];
        $entityIds       = $this->getContent()['entityIds'];
        $searchService   = $this->getContent()['searchService'];

        //I have to tell the system which search service we have to take (to have the correct connection)
        /** @var AbstractSearchService $searchServiceInstance */
        $searchServiceInstance = $this->container->get($searchService);

        //Grab the entities
        $entities = [];

        foreach ($entityIds as $entityId) {

            $entity = $this->entityManager->getRepository($entityClassName)->find($entityId);

            Assert::isInstanceOf($entity, class: HasSearchInterface::class);

            //We already know that the entities are all the same type
            $entities[] = $entity;
        }

        $searchServiceInstance->updateEntities($entities);

        return null;
    }
}
