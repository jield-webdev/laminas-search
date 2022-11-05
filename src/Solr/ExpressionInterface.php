<?php

declare(strict_types=1);

namespace Jield\Search\Solr;

interface ExpressionInterface
{
    public function isEqual(string $expr): bool;

    public function __toString(): string;
}
