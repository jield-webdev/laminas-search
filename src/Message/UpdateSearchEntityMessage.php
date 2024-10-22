<?php

namespace Jield\Search\Message;


final class UpdateSearchEntityMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly int    $entityId,
        private readonly string $searchService,
    )
    {

    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getSearchService(): string
    {
        return $this->searchService;
    }
}
