<?php

declare(strict_types=1);

namespace Jield\Search\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Solarium\Client;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

/**
 * Solarium result paginator.
 */
class SolariumPaginator implements AdapterInterface
{
    protected ?int $count = null;

    public function __construct(protected Client $client, protected Query $query)
    {
    }

    public function count(): int
    {
        if (null === $this->count) {
            $this->getItems(offset: 0, itemCountPerPage: 0);
        }

        return $this->count;
    }

    public function getItems($offset, $itemCountPerPage): iterable|Result|ResultInterface
    {
        $this->query->setStart(start: $offset);
        $this->query->setRows(rows: $itemCountPerPage);
        $result = $this->client->select(query: $this->query);
        $this->count = $result->getNumFound();

        return $result;
    }
}
