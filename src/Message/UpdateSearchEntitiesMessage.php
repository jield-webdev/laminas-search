<?php

namespace Jield\Search\Message;


use Jield\Search\Service\SearchServiceInterface;
use Webmozart\Assert\Assert;

final class UpdateSearchEntitiesMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly array  $entityIds,
        private readonly array  $searchServices,
    )
    {
        //All search services have to implement the SearchServiceInterface
        Assert::allImplementsInterface(value: $searchServices, interface: SearchServiceInterface::class);

        //All entity ids have to be integers
        Assert::allInteger($entityIds);
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    public function getSearchServices(): array
    {
        return $this->searchServices;
    }
}
