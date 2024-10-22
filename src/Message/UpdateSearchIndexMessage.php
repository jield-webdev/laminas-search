<?php

namespace Jield\Search\Message;


final class UpdateSearchIndexMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly string $searchService,
    )
    {
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getSearchService(): string
    {
        return $this->searchService;
    }


}
