<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use UnexpectedValueException as BaseUnexpectedValueException;

class UnexpectedValueException extends BaseUnexpectedValueException implements ExceptionInterface
{
}
