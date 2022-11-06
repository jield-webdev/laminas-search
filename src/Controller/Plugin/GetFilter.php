<?php

declare(strict_types=1);

namespace Jield\Search\Controller\Plugin;

use Doctrine\Common\Collections\Criteria;
use JetBrains\PhpStorm\Pure;
use Jield\Search\ValueObject\DateInterval;
use Jield\Search\ValueObject\SearchFormResult;
use Laminas\Http\Request;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\ContainerInterface;

use function http_build_query;
use function urldecode;
use function urlencode;

final class GetFilter extends AbstractPlugin
{
    private SearchFormResult $filter;

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function __invoke(): GetFilter
    {
        /** @var Application $application */
        $application = $this->container->get('application');
        $encodedFilter = urldecode(
            string: (string)$application->getMvcEvent()->getRouteMatch()?->getParam(
            name: 'encodedFilter'));
        /** @var Request $request */
        $request = $application->getMvcEvent()->getRequest();


        //Initiate the filter
        $this->filter = new SearchFormResult(
            order: $request->getQuery(name: 'order', default: 'default'),
            direction: $request->getQuery(name: 'direction', default: Criteria::ASC)
        );

        if (!empty($encodedFilter)) {
            $this->filter->updateFromEncodedFilter(encodedFilter: $encodedFilter);
        }

        // If the form is submitted, refresh the URL
        if ($request->getQuery(name: 'query') !== null) {
            $this->filter->setQuery(query: $request->getQuery(name: 'query'));
            $this->filter->setFilter(filter: $request->getQuery(name: 'filter', default: []));
        }

        if (null !== $request->getQuery(name: 'facet')) {
            $this->filter->setFacet(facet: $request->getQuery(name: 'facet'));
        }

        if (!empty($request->getQuery(name: 'dateInterval'))) {
            $dateInterval = DateInterval::fromValue(value: $request->getQuery(name: 'dateInterval'));
            $this->filter->setDateInterval(dateInterval: $dateInterval);
        }

        if (null !== $request->getQuery(name: 'order')) {
            $this->filter->setOrder(order: $request->getQuery(name: 'order'));
        }

        if (null !== $request->getQuery(name: 'direction')) {
            $this->filter->setDirection(direction: $request->getQuery(name: 'direction'));
        }

        // If the form is submitted, refresh the URL
        if ($request->getQuery(name: 'reset') !== null) {
            $this->filter = new SearchFormResult(
                order: $request->getQuery(name: 'order', default: 'default'),
                direction: $request->getQuery(name: 'direction', default: Criteria::ASC)
            );
        }

        return $this;
    }

    public function getFilter(): SearchFormResult
    {
        return $this->filter;
    }

    #[Pure] public function getOrder(): string
    {
        return $this->filter->getOrder();
    }

    #[Pure] public function getDirection(): string
    {
        return $this->filter->getDirection();
    }

    #[Pure] public function getQuery(): ?string
    {
        return $this->filter->getQuery();
    }

    #[Pure] public function getFacet(): array
    {
        return $this->filter->getFacet();
    }

    public function getDateInterval(): DateInterval
    {
        return $this->filter->getDateInterval();
    }

    public function getEncodedFilter(): ?string
    {
        return urlencode(string: $this->filter->getHash());
    }

    public function setFacetByKey(string $facetKey, array|string $value): void
    {
        if ($this->request->getQuery('query') === null) {
            $this->filter->setFacetByKey($facetKey, $value);
        }
    }

    public function setFilterByKey(string $filterKey, array|string|int $value): void
    {
        if ($this->request->getQuery('query') === null) {
            $this->filter->setFilterByKey($filterKey, $value);
        }
    }

    public function parseFilteredSortQuery(array $removeParams = []): string
    {
        $filterCopy = $this->getFilterFormData();
        foreach ($removeParams as $param) {
            unset($filterCopy[$param]);
        }

        return http_build_query(data: ['filter' => $this->getFilterFormData(), 'submit' => 'true']);
    }

    public function getFilterFormData(): array
    {
        return [
            'filter' => $this->filter->getFilter(),
            'facet' => $this->filter->getFacet(),
            'query' => $this->filter->getQuery(),
            'dateInterval' => $this->filter->getDateInterval()->toValue(),
        ];
    }
}
