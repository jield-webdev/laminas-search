<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use OutOfBoundsException as BaseOutOfBoundsException;

class OutOfBoundsException extends BaseOutOfBoundsException implements ExceptionInterface
{
}
