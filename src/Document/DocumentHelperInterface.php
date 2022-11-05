<?php

declare(strict_types=1);

namespace Jield\Search\Document;

use Jield\Search\Entity\HasSearchInterface;
use Solarium\Core\Query\DocumentInterface;

interface DocumentHelperInterface
{
    public function getDocument(DocumentInterface $document, HasSearchInterface $entity): DocumentInterface;
}
