<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\ExpressionInterface;
use Stringable;

class FunctionExpression extends Expression implements Stringable
{
    /**
     * @param Expression|string $function
     * @param ExpressionInterface|array|null $parameters
     */
    public function __construct(private $function, private $parameters = null)
    {
    }

    #[Pure] public function __toString(): string
    {
        $parameters = $this->parameters ?: null;

        if ($parameters && !$parameters instanceof ParameterExpression) {
            $parameters = new ParameterExpression($parameters);
        }

        return $this->function . '(' . $parameters . ')';
    }
}
