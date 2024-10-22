<?php

namespace Jield\Search\Message;


final class UpdateSearchEntitiesMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly array  $entityIds,
        private readonly string $searchService,
    )
    {
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    public function getSearchService(): string
    {
        return $this->searchService;
    }
}
