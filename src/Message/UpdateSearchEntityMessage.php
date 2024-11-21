<?php

namespace Jield\Search\Message;


use Jield\Search\Service\SearchServiceInterface;
use Webmozart\Assert\Assert;

final class UpdateSearchEntityMessage
{
    public function __construct(
        private readonly string $entityClassName,
        private readonly int    $entityId,
        private readonly array  $searchServices,
    )
    {
        Assert::allImplementsInterface($searchServices, interface: SearchServiceInterface::class);
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getSearchServices(): array
    {
        return $this->searchServices;
    }
}
