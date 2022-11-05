<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use OutOfRangeException as BaseOutOfRangeException;

class OutOfRangeException extends BaseOutOfRangeException implements ExceptionInterface
{
}
