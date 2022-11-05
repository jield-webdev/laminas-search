<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Jield\Search\Solr\Util;
use Stringable;

use function count;
use function current;
use function key;

class LocalParamsExpression extends Expression implements Stringable
{
    /**
     * @param Expression|string $type
     * @param mixed[] $params
     */
    public function __construct(private $type, private readonly array $params = [], private readonly bool $shortForm = true)
    {
    }

    public function __toString(): string
    {
        $typeString   = $this->shortForm ? $this->type : 'type=' . $this->type;
        $paramsString = $this->buildParamString();

        return '{!' . $typeString . $paramsString . '}';
    }

    private function buildParamString(): string
    {
        if ($this->shortForm && count($this->params) === 1 && key($this->params) === $this->type) {
            return '=' . Util::sanitize(current($this->params));
        }

        $paramsString = '';

        foreach ($this->params as $key => $value) {
            $paramsString .= ' ' . $key . '=' . Util::sanitize($value);
        }

        return $paramsString;
    }
}
