<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Jield\Search\Solr\ExpressionInterface;
use Stringable;

/**
 * Base class for expressions
 *
 * The base class for query expressions provides methods to escape and quote query strings as well being the object to
 * create literal queries which should not be escaped
 */
class Expression implements ExpressionInterface, Stringable
{
    /**
     * Create new expression object
     *
     * @param Expression|string $expr
     */
    public function __construct(protected $expr)
    {
    }

    public function isEqual(string $expr): bool
    {
        return $expr === (string)$this;
    }

    public function __toString(): string
    {
        return (string) $this->expr;
    }
}
