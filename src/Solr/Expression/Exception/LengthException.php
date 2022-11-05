<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use LengthException as BaseLengthException;

class LengthException extends BaseLengthException implements ExceptionInterface
{
}
