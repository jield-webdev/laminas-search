<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Jield\Search\Solr\ExpressionInterface;
use Jield\Search\Solr\Util;
use Stringable;

use function sprintf;

/**
 * Range expression class
 *
 * Let you specify range queries in the like of field:[<start> TO <end>] or field:{<start> TO <end>}
 */
class RangeExpression extends Expression implements Stringable
{
    /**
     * Create new range query object
     *
     * @param string|int|Expression $start
     * @param string|int|Expression $end
     */
    public function __construct(
        protected $start = null,
        protected $end = null,
        /**
         * Inclusive or exclusive the range start/end?
         */
        protected bool $inclusive = true
    )
    {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s TO %s%s',
            $this->inclusive ? '[' : '{',
            $this->cast($this->start),
            $this->cast($this->end),
            $this->inclusive ? ']' : '}'
        );
    }

    /**
     * @param ExpressionInterface|string|null $value
     */
    private function cast($value): ExpressionInterface|string
    {
        return $value === null ? '*' : Util::sanitize($value);
    }
}
