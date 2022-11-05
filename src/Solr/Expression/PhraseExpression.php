<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use Jield\Search\Solr\Util;
use Stringable;

/**
 * Class for query phrases
 *
 * Phrases are grouped terms for exact matching in the like of "word1 word2"
 */
class PhraseExpression extends Expression implements Stringable
{
    public function __toString(): string
    {
        return Util::quote($this->expr);
    }
}
