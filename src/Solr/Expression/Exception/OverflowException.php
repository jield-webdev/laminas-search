<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use OverflowException as BaseOverflowException;

class OverflowException extends BaseOverflowException implements ExceptionInterface
{
}
