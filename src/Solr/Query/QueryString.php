<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Query;

use DateTime;
use Jield\Search\Solr\Expression\DateTimeExpression;
use Jield\Search\Solr\Expression\GroupExpression;
use Jield\Search\Solr\Util;
use Stringable;

use function is_array;
use function is_bool;
use function strtr;

class QueryString implements Stringable
{
    private array $placeholders = [];

    public function __construct(private readonly string $query)
    {
    }

    /**
     * Add a value for a placeholder
     */
    public function setPlaceholder(string $placeholder, mixed $value): self
    {
        $this->placeholders[$placeholder] = $value;

        return $this;
    }

    /**
     * Add values for several placeholders as key => value pairs
     *
     * @param mixed[] $placeholders
     */
    public function setPlaceholders(array $placeholders): self
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    /** Return string representation */
    public function __toString(): string
    {
        $replacements = [];

        foreach ($this->placeholders as $placeholder => $value) {
            if ($value instanceof DateTime) {
                $value = new DateTimeExpression(date: $value);
            } elseif (is_array(value: $value)) {
                $value = new GroupExpression(expressions: $value);
            } elseif (is_bool(value: $value)) {
                $value = $value ? 'true' : 'false';
            } else {
                $value = Util::sanitize(value: $value);
            }

            $replacements['<' . $placeholder . '>'] = (string)$value;
        }

        return strtr($this->query, $replacements);
    }
}
