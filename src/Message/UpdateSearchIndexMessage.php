<?php

namespace Jield\Search\Message;


use Jield\Search\Service\SearchServiceInterface;
use Webmozart\Assert\Assert;

final class UpdateSearchIndexMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly array  $searchServices,
    )
    {
        //All search services have to implement the SearchServiceInterface
        Assert::allImplementsInterface($searchServices, SearchServiceInterface::class);
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getSearchServices(): array
    {
        return $this->searchServices;
    }
}
