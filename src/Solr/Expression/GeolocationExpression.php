<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Stringable;

use function sprintf;

class GeolocationExpression extends Expression implements Stringable
{
    public function __construct(private readonly float $latitude, private readonly float $longitude, private readonly int $precision)
    {
    }

    public function __toString(): string
    {
        return sprintf('%.' . $this->precision . 'F,%.' . $this->precision . 'F', $this->latitude, $this->longitude);
    }
}
