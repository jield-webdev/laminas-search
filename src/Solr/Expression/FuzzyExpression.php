<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\Util;
use Stringable;

/**
 * Class for fuzzy query expressions
 */
class FuzzyExpression extends Expression implements Stringable
{
    /**
     * Similarity (0.0 to 1.0)
     */
    private float $similarity;

    /**
     * Create new fuzzy query object
     *
     * @param string|Expression $expr
     */
    #[Pure] public function __construct($expr, ?float $similarity = null)
    {
        parent::__construct(expr: $expr);

        if ($similarity !== null) {
            $this->similarity = $similarity;
        }
    }

    public function __toString(): string
    {
        return Util::escape(value: $this->expr) . '~' . $this->similarity;
    }
}
