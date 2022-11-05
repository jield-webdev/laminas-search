<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use JetBrains\PhpStorm\Pure;
use Stringable;

use function array_merge;

class GeofiltExpression extends Expression implements Stringable
{
    private $distance;

    /**
     * @param mixed[] $additionalParams
     */
    public function __construct(
        private readonly string $field,
        private readonly ?GeolocationExpression $geolocation = null,
        ?int $distance = null,
        private readonly array $additionalParams = []
    ) {
        $this->distance = (int)$distance;
    }

    #[Pure] public function __toString(): string
    {
        $params = ['sfield' => $this->field];

        if ($this->geolocation) {
            $params['pt'] = (string)$this->geolocation;
        }

        if ($this->distance) {
            $params['d'] = $this->distance;
        }

        $params = array_merge($params, $this->additionalParams);

        return (string) new LocalParamsExpression('geofilt', $params);
    }
}
