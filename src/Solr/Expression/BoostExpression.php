<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\ExpressionInterface;
use Jield\Search\Solr\Util;
use Stringable;

/**
 * Class representing boosted queries
 *
 * Class to construct boosted queries in the like of <term>^<boost>
 */
class BoostExpression extends Expression implements Stringable
{
    /**
     * @param ExpressionInterface|string|null $expr
     */
    #[Pure] public function __construct(private readonly float $boost, $expr)
    {
        $expr = Util::escape(value: $expr);
//        $expr = trim($expr);

        parent::__construct(expr: $expr);
    }

    public function __toString(): string
    {
        return Util::sanitize(value: $this->expr) . '^' . $this->boost;
    }
}
