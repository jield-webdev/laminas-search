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
use Laminas\Stdlib\RequestInterface;
use Psr\Container\ContainerInterface;

use function http_build_query;
use function urldecode;
use function urlencode;

class GetFilter extends AbstractPlugin
{
    private SearchFormResult $filter;

    protected ?string $encodedFilter;

    private readonly Request|RequestInterface $request;

    public function __construct(private readonly ContainerInterface $container)
    {
        /** @var Application $application */
        $application = $this->container->get('application');

        $this->encodedFilter = urldecode(
            string: (string)$application->getMvcEvent()->getRouteMatch()?->getParam(
                name: 'encodedFilter'
            )
        );

        /** @var Request $request */
        $this->request = $application->getMvcEvent()->getRequest();
    }

    public function __invoke(): GetFilter
    {
        //Make local variables
        $request       = $this->request;
        $encodedFilter = $this->encodedFilter;

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

        if ($this->request->isPost()) {
            $this->filter = new SearchFormResult(
                order: $this->request->getQuery(name: 'order', default: 'default'),
                direction: $this->request->getQuery(name: 'direction', default: Criteria::ASC),
                query: $this->request->getPost(name: 'query'),
                filter: $this->request->getPost(name: 'filter', default: []),
                facet: $this->request->getPost(name: 'facet', default: []),
                dateInterval: $this->request->getPost(name: 'dateInterval', default: new DateInterval()),
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
            $this->filter->setFacetByKey(key: $facetKey, value: $value);
        }
    }

    public function setFilterByKey(string $filterKey, array|string|int $value): void
    {
        if ($this->request->getQuery('query') === null) {
            $this->filter->setFilterByKey(key: $filterKey, value: $value);
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
            'filter'       => $this->filter->getFilter(),
            'facet'        => $this->filter->getFacet(),
            'query'        => $this->filter->getQuery(),
            'dateInterval' => $this->filter->getDateInterval()->toValue(),
        ];
    }
}
