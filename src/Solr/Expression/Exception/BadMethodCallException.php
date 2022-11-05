<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use BadMethodCallException as BaseBadMethodCallException;

class BadMethodCallException extends BaseBadMethodCallException implements ExceptionInterface
{
}
