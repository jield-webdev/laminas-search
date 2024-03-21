<?php

declare(strict_types=1);

namespace Jield\Search\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\Job\UpdateSearchEntities;
use Jield\Search\Job\UpdateSearchEntity;
use Jield\Search\Job\UpdateSearchIndex;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use Webmozart\Assert\Assert;

class SearchQueueService
{
    private DoctrineQueue $queue;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array              $cores,
        private readonly JobPluginManager   $jobPluginManager,
        private readonly QueuePluginManager $queuePluginManager
    )
    {
        $this->queue = $this->queuePluginManager->get(name: 'search');
    }

    public function updateEntity(HasSearchInterface $entity, bool $queued = false): void
    {
        if ($queued) {
            $job = $this->jobPluginManager->get(name: UpdateSearchEntity::class);
            $job->setContent([
                'entityClassName' => $entity::class,
                'entityId'        => $entity->getId(),
                'searchService'   => $this->findSearchServiceFromEntity(entity: $entity),
            ]);

            $this->queue->push(job: $job);

            return;
        }

        $searchServiceName = $this->findSearchServiceFromEntity(entity: $entity);
        /** @var AbstractSearchService $searchServiceInstance */
        $searchServiceInstance = $this->container->get($searchServiceName);
        $searchServiceInstance->updateEntity(entity: $entity);
    }

    public function updateEntities(array|Collection $entities): void
    {
        $job = $this->jobPluginManager->get(name: UpdateSearchEntities::class);

        //The array cannot be emtpy
        if (empty($entities)) {
            return;
        }

        //When doing this, the entities _must_ be of the same class
        //Use the entity class name of the first entity
        $entityClassName = $entities[0]::class;
        //We only push the ID's and the classname and the service into the job
        $entityIds = [];
        foreach ($entities as $updateAbleEntity) {

            //We only allow entities of the same class
            if ($updateAbleEntity::class !== $entityClassName) {
                throw new \InvalidArgumentException('All entities must be of the same class');
            }

            $entityIds[] = $updateAbleEntity->getId();
        }


        $job->setContent([
            'entityClassName' => $entityClassName,
            'entityIds'       => $entityIds,
            'searchService'   => $this->findSearchServiceFromEntity(entity: new $entityClassName()),
        ]);

        $this->queue->push(job: $job);
    }

    public function updateIndex(string $entityClassName): void
    {
        //Sometimes we get proxies, then we need to get the real class
        $entityClassName = ClassUtils::getRealClass(className: $entityClassName);

        //Only entity classnames which implement interface can be used
        Assert::isInstanceOf(new $entityClassName(), class: HasSearchInterface::class);

        $job = $this->jobPluginManager->get(name: UpdateSearchIndex::class);
        $job->setContent([
            'entityClassName' => $entityClassName,
            'searchService'   => $this->findSearchServiceFromEntity(entity: new $entityClassName()),
        ]);

        $this->queue->push(job: $job);
    }

    private function findSearchServiceFromEntity(HasSearchInterface $entity): string
    {
        //Sometimes we get proxies, then we need to get the real class
        $entityClassName = ClassUtils::getRealClass(className: $entity::class);

        foreach ($this->cores as $core) {
            if ($core['entity'] === $entityClassName) {
                return $core['service'];
            }
        }

        throw new \InvalidArgumentException('No search service found for entity ' . $entityClassName);
    }
}
