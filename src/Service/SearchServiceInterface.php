<?php

declare(strict_types=1);

namespace Jield\Search\Service;

use Jield\Search\Entity\HasSearchInterface;
use Solarium\Client;
use Solarium\QueryType\Update\Result;

/**
 * Interface SearchServiceInterface
 */
interface SearchServiceInterface
{
    public function getSolrClient(): Client;

    public function clearIndex(bool $optimize = true): Result;

    public function deleteDocument(HasSearchInterface $entity, bool $optimize = true): Result;
}
