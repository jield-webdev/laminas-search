<?php

declare(strict_types=1);

namespace Jield\Search\Solr\Expression;

use DateTime;
use DateTimeZone;
use JetBrains\PhpStorm\Pure;
use Jield\Search\Solr\Expression\Exception\InvalidArgumentException;
use Jield\Search\Solr\ExpressionInterface;
use Jield\Search\Solr\Util;

use function array_filter;
use function array_pop;
use function array_shift;
use function array_unshift;
use function end;
use function func_get_args;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_numeric;
use function is_object;
use function is_string;
use function strtolower;
use function trim;

class ExpressionBuilder
{
    private DateTimeZone|string $defaultTimezone = 'UTC';

    /**
     * Set default timezone for the Solr search server
     *
     * The default timezone is used to convert date queries. You can either
     * pass a string (like "Europe/Berlin") or a DateTimeZone object.
     *
     * @throws InvalidArgumentException
     */
    public function setDefaultTimezone(DateTimeZone|string $timezone): void
    {
        if (!is_string(value: $timezone) && !is_object(value: $timezone)) {
            throw InvalidArgumentException::invalidArgument(
                position: 1,
                name: 'timezone',
                expectation: ['string', DateTimeZone::class],
                actual: $timezone);
        }

        $this->defaultTimezone = $timezone;
    }

    /**
     * Create phrase expression: "term1 term2"
     */
    #[Pure] public function phrase(?string $str): ?ExpressionInterface
    {
        if ($this->ignore(expr: $str)) {
            return null;
        }

        return new PhraseExpression(expr: $str);
    }

    private function ignore(mixed $expr): bool
    {
        return $expr === null || (is_string(value: $expr) && trim(string: $expr) === '');
    }

    /**
     * Create boost expression: <expr>^<boost>
     *
     * @param ExpressionInterface|string|null $expr
     */
    #[Pure] public function boost($expr, ?float $boost): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr) or $this->ignore(expr: $boost)) {
            return null;
        }

        return new BoostExpression(boost: $boost, expr: $expr);
    }

    /**
     * Create proximity match expression: "<word1> <word2>"~<proximity>
     *
     * @param int|mixed $proximity
     */
    public function prx(ExpressionInterface|string|null $word = null, $proximity = null): ?ExpressionInterface
    {
        $arguments = func_get_args();
        $proximityElement = array_pop(array: $arguments);

        $arguments = $this->flatten(collection: $arguments);

        if (!$arguments) {
            return null;
        }

        return new ProximityExpression(words: $arguments, proximity: $proximityElement);
    }

    private function flatten($collection): array
    {
        $stack = [$collection];
        $result = [];

        while (!empty($stack)) {
            $item = array_shift(array: $stack);

            if (is_iterable(value: $item)) {
                foreach ($item as $element) {
                    array_unshift($stack, $element);
                }
            } else {
                array_unshift($result, $item);
            }
        }

        return $result;
    }

    /**
     * Create fuzzy expression: <expr>~<similarity>
     *
     * @param ExpressionInterface|string|null $expr
     * @param float $similarity Similarity between 0.0 und 1.0
     */
    #[Pure] public function fzz($expr, ?float $similarity = null): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return new FuzzyExpression(expr: $expr, similarity: $similarity);
    }

    /**
     * Range query expression (exclusive start/end): {start TO end}
     */
    #[Pure] public function btwnRange(
        ExpressionInterface|float|int|string|null $start = null,
        ExpressionInterface|float|int|string|null $end = null
    ): ExpressionInterface {
        return new RangeExpression(start: $start, end: $end, inclusive: false);
    }

    /**
     * Create wildcard expression: <prefix>?, <prefix>*, <prefix>?<suffix> or <prefix>*<suffix>
     *
     * @param ExpressionInterface|string $prefix
     */
    public function wild(
        ?string $wildcard = '*',
        ExpressionInterface|string $suffix = '*'
    ): ?ExpressionInterface {
        $wildcard = strtolower(string: (string)$wildcard);
        //$wildcard = str_replace(' ', '\ ', $wildcard);

        $wildcard = Util::escape(value: $wildcard);
//        $wildcard = Util::sanitize($wildcard);

        $prefix = '*';

        if (($this->ignore(expr: $prefix) && $this->ignore(expr: $suffix)) || $this->ignore(expr: $wildcard)) {
            return null;
        }

        return new WildcardExpression(wildcard: $wildcard, prefix: $prefix, suffix: $suffix);
    }

    /**
     * Create bool, prohibited expression using the NOT notation, usable in OR/AND expressions:
     * (*:* NOT <expr>), e.g. (*:* NOT fieldName:*)
     *
     * @param ExpressionInterface|string|null $expr
     * @return ExpressionInterface|null
     */
    #[Pure] public function not($expr): BooleanExpression|ExpressionInterface|null
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return new BooleanExpression(operator: BooleanExpression::OPERATOR_PROHIBITED, expr: $expr, useNotNotation: true);
    }

    /**
     * Create bool expression
     *
     *      true => required (+)
     *      false => prohibited (-)
     *      null => neutral (<empty>)
     *
     * @param ExpressionInterface|string|null $expr
     * @param bool|null $operator @codingStandardsIgnoreLine
     * @return ExpressionInterface|null
     */
    public function bool(
        $expr,
        $operator
    ): BooleanExpression|ExpressionInterface|string|null { // @codingStandardsIgnoreLine
        if ($operator === null) {
            return $expr;
        }

        if ($operator) {
            return $this->req(expr: $expr);
        } else {
            return $this->prhb(expr: $expr);
        }
    }

    /**
     * Create bool, required expression: +<expr>
     *
     * @param ExpressionInterface|string|null $expr
     * @return ExpressionInterface|null
     */
    #[Pure] public function req($expr): BooleanExpression|ExpressionInterface|null
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return new BooleanExpression(operator: BooleanExpression::OPERATOR_REQUIRED, expr: $expr);
    }

    /**
     * Create bool, prohibited expression: -<expr>
     *
     * @param ExpressionInterface|string|null $expr
     * @return ExpressionInterface|null
     */
    #[Pure] public function prhb($expr): BooleanExpression|ExpressionInterface|null
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return new BooleanExpression(operator: BooleanExpression::OPERATOR_PROHIBITED, expr: $expr);
    }

    /**
     * Create AND grouped expression: (<expr1> AND <expr2> AND <expr3>)
     *
     * @param ExpressionInterface[]|string[] $args
     */
    public function andX(...$args): ?ExpressionInterface
    {
        $args = $this->parseCompositeArgs(args: $args)[0];

        if (!$args) {
            return null;
        }

        return new GroupExpression(expressions: $args, type: GroupExpression::TYPE_AND);
    }

    /**
     * @param mixed[] $args
     * @return mixed[]
     */
    private function parseCompositeArgs(array $args): array
    {
        $args = $this->flatten(collection: $args);
        $type = CompositeExpression::TYPE_SPACE;

        if (CompositeExpression::isValidType(type: end(array: $args))) {
            $type = array_pop(array: $args);
        }

        $args = array_filter(array: $args, callback: $this->permit(...));

        if (!$args) {
            return [false, $type];
        }

        return [$args, $type];
    }

    /**
     * Create OR grouped expression: (<expr1> OR <expr2> OR <expr3>)
     *
     * @param array|ExpressionInterface[]|string[] $args
     */
    public function orX(...$args): ?ExpressionInterface
    {
        $args = $this->parseCompositeArgs(args: $args)[0];

        if (!$args) {
            return null;
        }

        return new GroupExpression(expressions: $args, type: GroupExpression::TYPE_OR);
    }

    /**
     * Returns a query "*:*" which means find all if $expr is empty
     *
     * @param ExpressionInterface|string|null $expr
     */
    public function all($expr = null): mixed
    {
        if ($this->permit(expr: $expr)) {
            return $expr;
        }

        return $this->field(field: $this->lit(expr: '*'), expr: $this->lit(expr: '*'));
    }

    #[Pure] private function permit(mixed $expr): bool
    {
        return !$this->ignore(expr: $expr);
    }

    /**
     * Create field expression: <field>:<expr>
     * of in an array $expr is given: <field>:(<expr1> <expr2> <expr3>...)
     */
    #[Pure] public function field(
        ExpressionInterface|string $field,
        array|ExpressionInterface|string|null|int|bool $expr
    ): ?ExpressionInterface {
        if (is_array(value: $expr)) {
            $expr = $this->grp(expr: $expr);
        } elseif ($this->ignore(expr: $expr)) {
            return null;
        }

        return new FieldExpression(field: $field, expr: $expr);
    }

    /**
     * Create grouped expression: (<expr1> <expr2> <expr3>)
     *
     * @param ExpressionInterface|string|null $expr
     * @param string|mixed $type
     */
    #[Pure] public function grp($expr = null, $type = CompositeExpression::TYPE_SPACE): ?ExpressionInterface
    {
        if (empty($expr)) {
            return null;
        }

        return new GroupExpression(expressions: $expr, type: $type);
    }

    /**
     * Return string treated as literal (unescaped, unquoted)
     *
     * @param ExpressionInterface|string|null $expr
     */
    #[Pure] public function lit($expr): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return new Expression(expr: $expr);
    }

    #[Pure] public function number($field, $expr): ?ExpressionInterface
    {
        if (!is_numeric(value: $expr)) {
            return null;
        }

        return new FieldExpression(field: $field, expr: $this->eq(expr: $expr));
    }

    /**
     * Create term expression: <expr>
     *
     * @param ExpressionInterface|string|null $expr
     */
    #[Pure] public function eq($expr): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        if ($expr instanceof ExpressionInterface) {
            return $expr;
        }

        return new PhraseExpression(expr: $expr);
    }

    /**
     * Create a date expression for a specific day
     *
     * @param DateTime|mixed $date
     */
    #[Pure] public function day($date = null): ?ExpressionInterface
    {
        if (!$date instanceof DateTime) {
            return null;
        }

        return $this->range(start: $this->startOfDay(date: $date), end: $this->endOfDay(date: $date));
    }

    /**
     * Range query expression (inclusive start/end): [start TO end]
     */
    #[Pure] public function range(
        ExpressionInterface|float|int|string|null $start = null,
        ExpressionInterface|float|int|string|null $end = null,
        bool $inclusive = true
    ): ExpressionInterface {
        return new RangeExpression(start: $start, end: $end, inclusive: $inclusive);
    }

    /**
     * Expression for the start of the given date
     */
    #[Pure] public function startOfDay(?DateTime $date = null, bool|string $timezone = false): ?ExpressionInterface
    {
        if ($date === null) {
            return null;
        }

        return new DateTimeExpression(
            date: $date,
            format: DateTimeExpression::FORMAT_START_OF_DAY,
            timezone: $timezone === false ? $this->defaultTimezone : $timezone
        );
    }

    /**
     * Expression for the end of the given date
     */
    #[Pure] public function endOfDay(?DateTime $date = null, bool|string $timezone = false): ?ExpressionInterface
    {
        if (!$date) {
            return null;
        }

        return new DateTimeExpression(
            date: $date,
            format: DateTimeExpression::FORMAT_END_OF_DAY,
            timezone: $timezone === false ? $this->defaultTimezone : $timezone
        );
    }

    /**
     * Create a range between two dates (one side may be unlimited which is indicated by passing null)
     */
    #[Pure] public function dateRange(
        ?DateTime $from = null,
        ?DateTime $to = null,
        bool $inclusive = true,
        bool|string $timezone = 'Europe/Amsterdam'
    ): ?ExpressionInterface {
        if ($from === null && $to === null) {
            return null;
        }

        return $this->range(
            start: $this->lit(expr: $this->date(date: $from, timezone: $timezone)),
            end: $this->lit(expr: $this->date(date: $to, timezone: $timezone)),
            inclusive: $inclusive
        );
    }

    #[Pure] public function date(?DateTime $date = null, bool|string $timezone = false): ExpressionInterface
    {
        if ($date === null) {
            return $this->lit(expr: '*');
        }

        return new DateTimeExpression(
            date: $date,
            format: DateTimeExpression::FORMAT_DEFAULT,
            timezone: $timezone === false ? $this->defaultTimezone : $timezone
        );
    }

    /**
     * Create a function expression of name $function
     *
     * You can either pass an array of parameters, a single parameter or a ParameterExpression
     *
     * @param array|ParameterExpressionInterface|string|null $parameters
     */
    #[Pure] public function func(string $function, $parameters = null): ExpressionInterface
    {
        return new FunctionExpression(function: $function, parameters: $parameters);
    }

    /**
     * Create a function parameters expression
     */
    public function params(mixed ...$parameters): ExpressionInterface
    {
        $parameters = $this->flatten(collection: $parameters);

        return new ParameterExpression(parameters: $parameters);
    }

    /**
     * @param mixed[]|mixed $params
     * @param bool|mixed $shortForm
     */
    public function localParams(string $type, $params = [], $shortForm = true): ?ExpressionInterface
    {
        $additional = null;

        if (!is_bool(value: $shortForm)) {
            $additional = $shortForm;
            $shortForm = true;
        } elseif (!is_array(value: $params)) {
            $additional = $params;
            $params = [];
        }

        if ($additional !== null) {
            return $this->comp(
                expr: new LocalParamsExpression(type: $type, params: $params, shortForm: $shortForm),
                type: $additional);
        }

        return new LocalParamsExpression(type: $type, params: $params, shortForm: $shortForm);
    }

    /**
     * Create composite expression: <expr1> <expr2> <expr3>
     *
     * @param ExpressionInterface|string|null $expr
     */
    public function comp($expr = null, ?string $type = CompositeExpression::TYPE_SPACE): ?ExpressionInterface
    {
        [$args, $type] = $this->parseCompositeArgs(args: func_get_args());

        if (!$args) {
            return null;
        }

        return new CompositeExpression(expressions: $args, type: $type);
    }

    /** @param mixed[] $additionalParams */
    #[Pure] public function geofilt(
        string $field,
        ?GeolocationExpression $geolocation = null,
        ?int $distance = null,
        array $additionalParams = []
    ): ExpressionInterface {
        return new GeofiltExpression(
            field: $field,
            geolocation: $geolocation,
            distance: $distance,
            additionalParams: $additionalParams);
    }

    /**
     * Create a geo location expression: "<latitude>,<longitude>" using the given precision
     */
    #[Pure] public function latLong(float $latitude, float $longitude, int $precision = 12): ExpressionInterface
    {
        return new GeolocationExpression(latitude: $latitude, longitude: $longitude, precision: $precision);
    }

    /** @param string|ExpressionInterface|null $expr */
    public function noCache($expr = null): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return $this->comp(expr: [$this->shortLocalParams(tag: 'cache', value: false), $expr], type: null);
    }

    #[Pure] private function shortLocalParams(ExpressionInterface|string $tag, mixed $value): LocalParamsExpression
    {
        return new LocalParamsExpression(type: $tag, params: [$tag => $value], shortForm: true);
    }

    /** @param string|ExpressionInterface|null $expr */
    public function tag(string $tagName, $expr = null): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return $this->comp(expr: [$this->shortLocalParams(tag: 'tag', value: $tagName), $expr], type: null);
    }

    /** @param string|ExpressionInterface|null $expr */
    public function excludeTag(string $tagName, $expr = null): ?ExpressionInterface
    {
        if ($this->ignore(expr: $expr)) {
            return null;
        }

        return $this->comp(expr: [$this->shortLocalParams(tag: 'ex', value: $tagName), $expr], type: null);
    }
}
