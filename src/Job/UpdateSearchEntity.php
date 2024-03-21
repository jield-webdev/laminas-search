<?php

declare(strict_types=1);

namespace Jield\Search\Job;

use Doctrine\ORM\EntityManager;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\AbstractJob;
use Webmozart\Assert\Assert;

final class UpdateSearchEntity extends AbstractJob
{

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager
    )
    {
    }

    public function execute(): ?int
    {
        $entityClassName = $this->getContent()['entityClassName'];
        $entityId        = $this->getContent()['entityId'];
        $searchService   = $this->getContent()['searchService'];

        //Just use the entityManager to get the entity
        /** @var HasSearchInterface $entity */
        $entity = $this->entityManager->getRepository($entityClassName)->find($entityId);

        Assert::isInstanceOf($entity, class: HasSearchInterface::class);

        //I have to tell the system which search service we have to take (to have the correct connection)
        /** @var AbstractSearchService $searchServiceInstance */
        $searchServiceInstance = $this->container->get($searchService);
        $searchServiceInstance->updateEntity($entity);

        return null;
    }
}
