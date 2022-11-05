<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use BadFunctionCallException as BaseBadFunctionCallException;

class BadFunctionCallException extends BaseBadFunctionCallException implements ExceptionInterface
{
}
