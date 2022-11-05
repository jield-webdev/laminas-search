<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use UnderflowException as BaseUnderflowException;

class UnderflowException extends BaseUnderflowException implements ExceptionInterface
{
}
