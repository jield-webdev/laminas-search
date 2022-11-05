<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\ExpressionInterface;
use Jield\Search\Solr\Util;
use Stringable;

/**
 * Boolean expression class
 *
 * Class to construct bool queries (+<term> or -<term>)
 */
class BooleanExpression extends Expression implements Stringable
{
    final public const OPERATOR_REQUIRED   = '+';
    final public const OPERATOR_PROHIBITED = '-';

    /**
     * Create new expression object
     *
     * @param ExpressionInterface|string $expr
     * @param bool $useNotNotation use the NOT notation: (*:* NOT <expr>), e.g. (*:* NOT fieldName:*)
     */
    #[Pure] public function __construct(private readonly string $operator, $expr, private readonly bool $useNotNotation = false)
    {
        parent::__construct(expr: $expr);
    }

    public function __toString(): string
    {
        return $this->useNotNotation
            ? '(*:* NOT ' . Util::escape(value: $this->expr) . ')'
            : $this->operator . Util::escape(value: $this->expr);
    }
}
