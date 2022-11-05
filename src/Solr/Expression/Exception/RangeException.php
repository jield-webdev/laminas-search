<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use RangeException as BaseRangeException;

class RangeException extends BaseRangeException implements ExceptionInterface
{
}
