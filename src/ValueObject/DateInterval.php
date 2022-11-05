<?php

declare(strict_types=1);

namespace Jield\Search\ValueObject;

use DateTime;
use JetBrains\PhpStorm\Pure;

final class DateInterval
{
    private const DATE_FORMAT = 'd-m-Y'; //This has to match the format in the JS file

    public function __construct(
        private ?DateTime $startDate = null,
        private ?DateTime $endDate = null,
    ) {
    }

    #[Pure] public static function fromValue(string $value): DateInterval
    {
        $dateArray = explode(separator: ' - ', string: $value);

        $startDate = DateTime::createFromFormat(format: self::DATE_FORMAT, datetime: $dateArray[0] ?? '');
        $endDate = DateTime::createFromFormat(format: self::DATE_FORMAT, datetime: $dateArray[1] ?? '');

        return new self(
            startDate: $startDate !== false ? $startDate : null,
            endDate: $endDate !== false ? $endDate : null,
        );
    }

    public function toValue(): string
    {
        if (!$this->hasStartAndEndDate()) {
            return '';
        }

        return $this->startDate?->format(format: self::DATE_FORMAT) . ' - ' . $this->endDate?->format(format: self::DATE_FORMAT);
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function hasStartAndEndDate(): bool
    {
        return $this->startDate !== null && $this->endDate !== null;
    }

}
