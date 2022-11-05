<?php

declare(strict_types=1);

namespace Jield\Search\Document;

use Jield\Search\Entity\HasSearchInterface;
use Solarium\QueryType\Update\Query\Document;

interface DocumentHelperInterface
{
    public function getDocument(Document $document, HasSearchInterface $entity): Document;
}
