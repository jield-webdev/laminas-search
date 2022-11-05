<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Jield\Search\Solr\Util;
use Stringable;

use function implode;

/**
 * Proximity query class
 *
 * Proximity queries allow to search for two words in a specific distance ("<word1> <word2>"~<proximity>)
 */
class ProximityExpression extends Expression implements Stringable
{
    /**
     * Create new proximity query object
     *
     * @param string[] $words
     */
    public function __construct(
        private readonly array $words,
        /**
         * Maximum distance between the two words
         */
        private readonly int $proximity
    )
    {
    }

    public function __toString(): string
    {
        return Util::quote(value: implode(separator: ' ', array: $this->words)) . '~' . $this->proximity;
    }
}
