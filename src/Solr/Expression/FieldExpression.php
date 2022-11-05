<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\Util;
use Stringable;

/**
 * Field query expression
 *
 * Class representing a query limited to specific fields (field:<value>)
 */
class FieldExpression extends Expression implements Stringable
{
    /**
     * Create new field query
     *
     * @param string|Expression $field
     * @param string|Expression $expr
     */
    #[Pure] public function __construct(private $field, $expr)
    {
        parent::__construct($expr);
    }

    public function __toString(): string
    {
        $field      = Util::escape($this->field);
        $expression = Util::sanitize($this->expr);

        if ($this->expr instanceof LocalParamsExpression) {
            return $expression . $field;
        }

        return $field . ':' . $expression;
    }
}
