<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

use function array_pop;
use function gettype;
use function implode;
use function is_object;
use function sprintf;

class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
    public static function invalidArgument(int $position, string $name, array|string $expectation, mixed $actual): self
    {
        $expectations = (array)$expectation;

        return new self(
            message: sprintf(
                'Invalid argument #%d $%s given: expected %s, got %s',
                $position,
                $name,
                self::formatExpectations(expectations: $expectations),
                self::getType(actual: $actual)
            )
        );
    }

    /** @param string[] $expectations */
    private static function formatExpectations(array $expectations): string
    {
        $last = array_pop(array: $expectations);

        if (!$expectations) {
            return $last;
        }

        return implode(separator: ', ', array: $expectations) . ' or ' . $last;
    }

    private static function getType(mixed $actual): string
    {
        return is_object(value: $actual) ? $actual::class : gettype($actual);
    }
}
