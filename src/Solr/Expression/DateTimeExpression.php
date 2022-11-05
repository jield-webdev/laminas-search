<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use DateTime;
use DateTimeZone;
use Stringable;

class DateTimeExpression extends Expression implements Stringable
{
    final public const FORMAT_DEFAULT      = 'Y-m-d\TH:i:s\Z';
    final public const FORMAT_START_OF_DAY = 'Y-m-d\T00:00:00\Z';
    final public const FORMAT_END_OF_DAY   = 'Y-m-d\T23:59:59\Z';

    private static ?DateTimeZone $utcTimezone = null;
    private readonly DateTime             $date;
    private readonly string               $format;

    /**
     * @param string|DateTimeZone $timezone
     */
    public function __construct(DateTime $date, ?string $format = null, private $timezone = 'UTC')
    {
        $this->date   = clone $date;
        $this->format = $format ?: static::FORMAT_DEFAULT;
    }

    public function __toString(): string
    {
        $date = $this->date;

        if ($this->timezone === 'UTC') {
            if (!self::$utcTimezone) {
                self::$utcTimezone = new DateTimeZone(timezone: 'UTC');
            }
            $date = $date->setTimeZone(timezone: self::$utcTimezone);
        } elseif ($this->timezone !== null) {
            if ($this->timezone instanceof DateTimeZone) {
                $date = $date->setTimeZone(timezone: $this->timezone);
            } else {
                $date = $date->setTimeZone(timezone: new DateTimeZone(timezone: $this->timezone));
            }
        }

        return $date->format(format: $this->format);
    }
}
