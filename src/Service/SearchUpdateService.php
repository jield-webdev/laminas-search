<?php

declare(strict_types=1);

namespace Jield\Search\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Message\UpdateSearchEntitiesMessage;
use Jield\Search\Message\UpdateSearchEntityMessage;
use Jield\Search\Message\UpdateSearchIndexMessage;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

class SearchUpdateService
{
    public function __construct(
        private readonly ContainerInterface  $container,
        private readonly MessageBusInterface $bus,
        private readonly array               $cores,
    )
    {
    }

    public function updateEntity(HasSearchInterface $entity, bool $queued = false): void
    {
        if ($queued) {
            $this->bus->dispatch(new UpdateSearchEntityMessage(
                entityClassName: $entity::class,
                entityId: $entity->getId(),
                searchServices: $this->findSearchServicesFromEntity(entity: $entity),
            ));

            return;
        }

        $searchServiceNames = $this->findSearchServicesFromEntity(entity: $entity);

        foreach ($searchServiceNames as $searchServiceName) {
            /** @var AbstractSearchService $searchServiceInstance */
            $searchServiceInstance = $this->container->get($searchServiceName);
            $searchServiceInstance->updateEntity(entity: $entity);
        }
    }

    public function deleteDocument(HasSearchInterface $entity): void
    {
        $searchServiceNames = $this->findSearchServicesFromEntity(entity: $entity);

        foreach ($searchServiceNames as $searchServiceName) {
            /** @var AbstractSearchService $searchServiceInstance */
            $searchServiceInstance = $this->container->get($searchServiceName);
            $searchServiceInstance->deleteDocument(entity: $entity);
        }
    }

    public function updateEntities(array|Collection $entities): void
    {
        //Always convert to array
        if ($entities instanceof Collection) {
            $entities = $entities->toArray();
        }

        //The array cannot be emtpy
        if (empty($entities)) {
            return;
        }

        //When doing this, the entities _must_ be of the same class
        //Use the entity class name of the first entity (use reset to get the last element)
        $firstEntity = reset(array: $entities);

        $entityClassName = $firstEntity::class;

        //We only push the ID's and the classname and the service into the job
        $entityIds = [];
        foreach ($entities as $updateAbleEntity) {
            //We only allow entities of the same class
            if (ClassUtils::getRealClass(className: $updateAbleEntity::class) !== ClassUtils::getRealClass(className: $entityClassName)) {
                throw new \InvalidArgumentException(message: 'All entities must be of the same class, got ' . ClassUtils::getRealClass(className: $updateAbleEntity::class) . ' and expected ' . $entityClassName);
            }

            $entityIds[] = $updateAbleEntity->getId();
        }

        $this->bus->dispatch(new UpdateSearchEntitiesMessage(
            entityClassName: $entityClassName,
            entityIds: $entityIds,
            searchServices: $this->findSearchServicesFromEntity(entity: $entityClassName),
        ));
    }

    public function updateIndex(string $entityClassName): void
    {
        //Sometimes we get proxies, then we need to get the real class
        $entityClassName = ClassUtils::getRealClass(className: $entityClassName);

        //Only entity classnames which implement interface can be used
        Assert::isInstanceOf(value: new $entityClassName(), class: HasSearchInterface::class);

        $this->bus->dispatch(new UpdateSearchIndexMessage(
            entityClassName: $entityClassName,
            searchServices: $this->findSearchServicesFromEntity(entity: $entityClassName),
        ));
    }

    private function findSearchServicesFromEntity(string|HasSearchInterface $entity): array
    {
        //Always cast to the string value
        if ($entity instanceof HasSearchInterface) {
            $entity = ClassUtils::getRealClass(className: $entity::class);
        }

        Assert::implementsInterface(value: $entity, interface: HasSearchInterface::class);

        $services = [];

        foreach ($this->cores as $core) {
            if ($core['entity'] === $entity) {
                $services[] = $core['service'];
            }
        }

        return array_unique(array: $services);
    }
}
