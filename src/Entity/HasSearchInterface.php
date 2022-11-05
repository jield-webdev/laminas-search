<?php

declare(strict_types=1);

namespace Jield\Search\Entity;

interface HasSearchInterface
{
    public function getSearchDocumentClass(): string;

    public function getResourceId(): string;

    public function getId(): ?int;
}
