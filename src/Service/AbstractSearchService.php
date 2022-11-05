<?php

declare(strict_types=1);

namespace Jield\Search\Service;

use Application\Entity\AbstractEntity;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Json\Json;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Jield\Search\Document\DocumentHelperInterface;
use Jield\Search\Entity\HasSearchInterface;
use Jield\Search\ValueObject\FacetField;
use Solarium\Client;
use Solarium\Component\FacetSet;
use Solarium\Core\Client\Adapter\Http;
use Solarium\Core\Query\DocumentInterface;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Update\Result;
use stdClass;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Webmozart\Assert\Assert;

use function defined;
use function sprintf;

abstract class AbstractSearchService implements SearchServiceInterface
{
    final public const DATE_SOLR = 'Y-m-d\TH:i:s\Z';
    final public const QUERY_TERM_BOOST = 30;
    public const SOLR_CONNECTION = 'default';

    protected ?Client $solrClient = null;

    protected ?Query $query = null;

    protected ?FacetSet $facetSet = null;

    protected array $facets = [];

    protected readonly TranslatorInterface $translator;

    protected readonly EntityManager $entityManager;

    private array $config;

    private readonly string $connection;

    public function __construct(protected readonly ContainerInterface $container)
    {
        $this->translator = $container->get(TranslatorInterface::class);
        $this->entityManager = $container->get(EntityManager::class);
        $this->config = $container->get('Config');

        $this->query = $this->getSolrClient()->createSelect();
        $this->facetSet = $this->query->getFacetSet();
    }

    public function getSolrClient(): Client
    {
        if (null === $this->solrClient && defined('static::SOLR_CONNECTION')) {
            $prefix = $this->config['solr']['prefix'] ?? '';
            $connection = static::SOLR_CONNECTION;
            if (!empty($prefix)) {
                $connection = sprintf('%s_%s', $prefix, $connection);
            }

            $this->connection = $connection;

            $params = $this->config['solr']['connection'][$connection] ?? [];

            //Only change the core when this is different from the already given core
            if (!isset($params['endpoint']['server']['core'])) {
                $params['endpoint']['server']['core'] = $connection;
            }

            if (isset($this->config['solr']['host'])) {
                $params['endpoint']['server']['host'] = $this->config['solr']['host'];
            }

            if (isset($this->config['solr']['username'])) {
                $params['endpoint']['server']['username'] = $this->config['solr']['username'];
                $params['endpoint']['server']['password'] = $this->config['solr']['password'];
            }

            $adapter = new Http();

            if (isset($this->config['solr']['timeout']) && $this->config['solr']['timeout']) {
                $adapter->setTimeout((int)$this->config['solr']['timeout']);
            }

            $eventDispatcher = new EventDispatcher();

            $this->solrClient = new Client($adapter, $eventDispatcher, $params);
        }

        return $this->solrClient;
    }

    public function parseDateInterval(array $data): stdClass
    {
        //Create the date
        $fromDate = null;
        $toDate = null;

        if (isset($data['filter']['dateInterval'])) {
            $dateInterval = $data['filter']['dateInterval'];

            switch ($dateInterval) {
                case 'upcoming':
                    $fromDate = new DateTime();
                    break;
                case 'next2weeks':
                    $fromDate = new DateTime();
                    $toDate = new DateTime();
                    $toDate->add(new DateInterval('P2W'));
                    break;
                case 'today':
                    $fromDate = new DateTime();
                    $fromDate->setTime(0, 0);

                    $toDate = clone $fromDate;
                    $toDate->add(new DateInterval('P1D'));

                    break;
                case 'older':
                    $toDate = new DateTime();
                    $toDate->sub(new DateInterval('P12M'));
                    break;
                case 'P1M':
                case 'P3M':
                case 'P6M':
                case 'P12M':
                    $fromDate = new DateTime();
                    $fromDate->sub(new DateInterval($dateInterval));
                    $toDate = new DateTime();
                    break;
            }
        }

        $class = new stdClass();
        $class->fromDate = $fromDate;
        $class->toDate = $toDate;

        return $class;
    }

    public function updateEntity(HasSearchInterface $entity): void
    {
        $update = $this->getSolrClient()->createUpdate();

        $document = $this->getSearchDocumentFromEntity(update: $update, entity: $entity);

        $update->addDocument($document);
        $update->addCommit();

        $this->getSolrClient()->update($update);
    }

    protected function getSearchDocumentFromEntity(
        \Solarium\QueryType\Update\Query\Query $update,
        HasSearchInterface $entity
    ): DocumentInterface {
        //We need the helper to create the document for search
        if (!$this->container->has($entity->getSearchDocumentClass())) {
            throw new RuntimeException(
                'No search document helper (' . $entity->getSearchDocumentClass(
                ) . ') registered for ' . $entity::class . ', did you register it in the service manager?'
            );
        }

        /** @var DocumentHelperInterface $searchDocumentHelper */
        $searchDocumentHelper = $this->container->get($entity->getSearchDocumentClass());

        Assert::implementsInterface(value: $searchDocumentHelper, interface: DocumentHelperInterface::class);

        return $searchDocumentHelper->getDocument(document: $update->createDocument(), entity: $entity);
    }

    public function updateEntities(array|Collection $entities): void
    {
        Assert::allImplementsInterface(value: $entities, interface: HasSearchInterface::class);

        $update = $this->getSolrClient()->createUpdate();

        foreach ($entities as $entity) {
            $document = $this->getSearchDocumentFromEntity(update: $update, entity: $entity);
            $update->addDocument($document);
        }

        $update->addCommit();

        $this->getSolrClient()->update($update);
    }

    public function updateCollection(
        OutputInterface $output,
        HasSearchInterface $entity,
        bool $clearIndex = false,
        int $limit = 50,
        array $criteria = []
    ): void {
        $output->writeln('');
        $output->writeln(sprintf('<info>%s</info>', $entity::class));

        if ($clearIndex) {
            $output->writeln('<error>Index cleared</error>');
            $this->clearIndex();
        }

        if (!$clearIndex) {
            //Always to a check to delete items which are not in the database anymore
            $this->removeDeletedItemsFromIndex($output, $entity);
        }

        $amount = $this->findCount(entity: $entity::class, criteria: $criteria);

        $output->writeln(sprintf('Updating %d of %s', $amount, $entity::class));

        $i = 0;
        $total = 1;
        while ($i < $amount) {
            $update = $this->getSolrClient()->createUpdate();

            $elements = $this->findSliced(entity: $entity::class, limit: $limit, offset: $i, criteria: $criteria);

            foreach ($elements as $element) {
                $document = $this->getSearchDocumentFromEntity(update: $update, entity: $element);

                $update->addDocument(document: $document);
                $output->write('.');

                if ($total % 100 === 0) {
                    $output->write(' (' . number_format(($total / $amount * 100), 0) . ' %)');
                    $output->writeln('');
                }

                $total++;
            }

            $update->addCommit();
            $this->updateIndex(output: $output, update: $update);

            //clear the entity manager to prevent piling up entities
            $this->entityManager->clear();

            $i += $limit;
        }

        if ($amount > 0) {
            $output->write(' <info>(' . number_format((($total - 1) / $amount * 100), 0) . ' %)</info>');
        }

        $output->writeln(['', '<comment>Update done</comment>']);
    }

    public function clearIndex(bool $optimize = true): Result
    {
        $update = $this->getSolrClient()->createUpdate();
        $update->addDeleteQuery('*:*');
        $update->addCommit();
        $result = $this->getSolrClient()->update($update);
        if ($optimize) {
            $this->optimizeIndex();
        }

        return $result;
    }

    /**
     * Optimize the current index
     *
     * @see http://wiki.apache.org/solr/SolrPerformanceFactors#Optimization_Considerations
     */
    public function optimizeIndex(): Result
    {
        $update = $this->getSolrClient()->createUpdate();
        $update->addOptimize(); // No params, just use Solr's default optimization settings

        return $this->getSolrClient()->update($update);
    }

    protected function removeDeletedItemsFromIndex(
        OutputInterface $output,
        HasSearchInterface $entity,
        array $criteria = []
    ): void {
        $searchIndexIds = $this->findAllIdsFromSearchIndex(entity: $entity);
        $databaseIds = $this->findAllIdsFromDatabase(entity: $entity, criteria: $criteria);

        $toBeDeletedItemsInSearchIndex = array_diff($searchIndexIds, $databaseIds);

        $update = $this->getSolrClient()->createUpdate();
        foreach ($toBeDeletedItemsInSearchIndex as $remainderId) {
            /**
             * To be able to delete by id we need to reconstruct the resource id, therefore we create a new entity
             */
            $entity = new $entity();
            $entity->setId($remainderId);

            $update->addDeleteById($entity->getResourceId());
            $output->writeln(sprintf('<comment>Id %d of %s has been deleted</comment>', $remainderId, $entity::class));
        }

        $output->writeln(
            sprintf('Deleted %d orphaned items', count($toBeDeletedItemsInSearchIndex))
        );

        $update->addCommit();
        $this->getSolrClient()->update($update);
    }

    protected function findAllIdsFromSearchIndex(HasSearchInterface $entity = null): array
    {
        //Get all ids from the index
        $query = $this->getSolrClient()->createSelect();
        $query->setQuery('*:*')->setRows(1000000);
        $ids = [];
        foreach ($this->getSolrClient()->select($query)->getIterator() as $document) {
            /**
             * The document->id is the resource id in SOLR (sprintf('%s-%s', $this->get('underscore_entity_name'), $this->getId());)
             *
             * For this function we are only interested in the "real" id, so we will remove the first part
             */
            $id = explode('-', $document->id);
            if (isset($id[1])) {
                $ids[] = (int)$id[1];
            }
        }

        return $ids;
    }

    private function findAllIdsFromDatabase(HasSearchInterface $entity, array $criteria): array
    {
        $results = $this->entityManager->getRepository($entity::class)->findBy($criteria, ['id' => Criteria::ASC]);

        $databaseIds = [];
        /** @var HasSearchInterface $singleResult */
        foreach ($results as $singleResult) {
            $databaseIds[] = $singleResult->getId();
        }

        return $databaseIds;
    }

    protected function findCount(string $entity, array $criteria): int
    {
        return $this->entityManager->getRepository($entity)->count($criteria);
    }

    protected function findSliced(string $entity, int $limit, int $offset, array $criteria = []): array
    {
        return $this->entityManager->getRepository($entity)->findBy($criteria, [], $limit, $offset);
    }

    protected function updateIndex(OutputInterface $output, \Solarium\QueryType\Update\Query\Query $update): void
    {
        try {
            $this->getSolrClient()->update($update);
        } catch (HttpException $e) {
            $responseBody = $e->getBody();

            if (!empty($responseBody)) {
                $response = Json::decode($responseBody);
                if (isset($response->responseHeader)) {
                    $output->writeln(
                        sprintf("<error>Solr HTTP response code: %s</error>", $response->responseHeader->status)
                    );
                }
                if (isset($response->error)) {
                    $output->writeln(sprintf("<error>Solr error message: %s</error>", $response->error->msg));
                }
            }
        }
    }

    public function syncIndex(OutputInterface $output, HasSearchInterface $entity, array $criteria = []): void
    {
        $output->writeln('');
        $output->writeln(sprintf('<info>%s</info>', $entity::class));

        $this->addMissingItemsToTheIndex(output: $output, entity: $entity, criteria: $criteria);
        $this->removeDeletedItemsFromIndex(output: $output, entity: $entity, criteria: $criteria);

        $output->writeln('<comment>Sync done</comment>');
    }

    private function addMissingItemsToTheIndex(
        OutputInterface $output,
        HasSearchInterface $entity,
        array $criteria = []
    ): void {
        $searchIndexIds = $this->findAllIdsFromSearchIndex(entity: $entity);
        $databaseIds = $this->findAllIdsFromDatabase(entity: $entity, criteria: $criteria);

        $toBeAddedItemsInSearchIndex = array_diff($databaseIds, $searchIndexIds);

        if (count($toBeAddedItemsInSearchIndex) > 200) {
            $output->writeln(
                sprintf(
                    '<error>%d items of %s will be added which is more than the threshold of 200 items, use a sync instead</error>',
                    count($toBeAddedItemsInSearchIndex),
                    $entity::class
                )
            );

            return;
        }

        $update = $this->getSolrClient()->createUpdate();
        foreach ($toBeAddedItemsInSearchIndex as $newId) {
            $entity = $this->entityManager->getRepository($entity::class)->find($newId);
            $document = $this->getSearchDocumentFromEntity(update: $update, entity: $entity);

            $update->addDocument(document: $document);

            $output->write('.');
        }

        $update->addCommit();
        $this->updateIndex(output: $output, update: $update);

        $output->writeln(sprintf('Added %d items', count($toBeAddedItemsInSearchIndex)));
    }

    public function deleteDocument(HasSearchInterface $entity, bool $optimize = false): Result
    {
        $update = $this->getSolrClient()->createUpdate();
        $update->addDeleteById($entity->getResourceId());
        $update->addCommit();
        $result = $this->getSolrClient()->update($update);
        if ($optimize) {
            $this->optimizeIndex();
        }

        return $result;
    }

    public function addFilterQuery($key, $value): void
    {
        $this->query->addFilterQuery(
            [
                'key' => $key,
                'query' => $key . ':(' . $value . ')',
                'tag' => $key,
            ]
        );
    }

    public function getResultSet(): \Solarium\Core\Query\Result\Result
    {
        return $this->getSolrClient()->select($this->getQuery());
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    protected function setQuery(Query $query): AbstractSearchService
    {
        $this->query = $query;

        //Default add 1000 results
        $this->query->setRows(1000);

        return $this;
    }

    public function getFacets(): array
    {
        return $this->facets;
    }

    public function createFacetFilters(array $facets): void
    {
        $key = 0;

        foreach ($facets as $field => $facet) {
            //Skip when we have no values
            if (!array_key_exists('values', $facet) || empty($facet['values'])) {
                continue;
            }

            //When we have an AND we need all values
            if (isset($facet['andOr']) && $facet['andOr'] === 'and') {
                foreach ($facet['values'] as $value) {
                    $this->addFilterQueryFromFacet(
                        key: $key++,
                        field: $field,
                        value: $value,
                        exclude: isset($facet['yesNo']) && $facet['yesNo'] === 'no',
                        and: true,
                    );
                }
            }

            //An or is just to put all values in the result
            if (!isset($facet['andOr'])) {
                $this->addFilterQueryFromFacet(
                    key: $key++,
                    field: $field,
                    value: $facet['values'],
                    exclude: isset($facet['yesNo']) && $facet['yesNo'] === 'no'
                );
            }
        }
    }

    private function addFilterQueryFromFacet(
        int $key,
        string $field,
        string|array|int $value,
        bool $exclude = false,
        bool $and = false,
    ): void {
        if (is_string($value)) {
            $value = sprintf('"%s"', $value);
        }

        if (is_array($value)) {
            $value = array_map(static fn(string $value) => sprintf('"%s"', $value), $value);

            $value = implode(' ', $value);
        }

        $query = sprintf('%s%s:(%s)', $exclude ? '-' : '', $field, $value);

        $this->query->createFilterQuery($key . $field)->setQuery($query)->getLocalParameters()->addTags(
            [$this->getFacet($field)->getField()]
        );

        if ($and) {
            $this->facetSet->getFacet($this->getFacet($field)->getField())->setMinCount(1);
        }
    }

    public function getFacet(string $fieldName): FacetField
    {
        return $this->facets[$fieldName];
    }

    public function testIndex(OutputInterface $output, HasSearchInterface $entity, bool $clearIndex): void
    {
        $output->writeln('');
        $output->writeln(sprintf('<info>%s</info>', $entity::class));

        $coreAdminQuery = $this->getSolrClient()->createCoreAdmin();

        if ($clearIndex) {
            $this->clearIndex();
            $output->writeln(sprintf('<error>Index of core %s cleared</error>', $this->connection));
        }

        // use the CoreAdmin query to build a Reload action
        $statusAction = $coreAdminQuery->createReload();
        $statusAction->setCore($this->connection);
        $coreAdminQuery->setAction($statusAction);

        $this->getSolrClient()->coreAdmin($coreAdminQuery);

        $output->writeln(sprintf('Reload of core %s done', $this->connection));

        //Grab an array with all ids from the database
        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb->select('e')->from($entity::class, 'e')->setMaxResults(1)->getQuery()->getResult();

        $update = $this->getSolrClient()->createUpdate();

        $requireDelete = false;

        if (count($result) === 0) {
            $entity = new $entity();

            $output->writeln(sprintf('<comment>Test with empty object %s</comment>', $entity::class));

            $requireDelete = true;
        } else {
            $entity = $result[0];
        }

        $document = $this->getSearchDocumentFromEntity(update: $update, entity: $entity);
        $update->addDocument(document: $document);
        $update->addCommit();

        try {
            $this->getSolrClient()->update($update);
            $output->writeln('<comment>Test successful</comment>');

            if ($requireDelete) {
                $this->deleteDocument($entity);
                $output->writeln('<comment>Test document deleted</comment>');
            }
        } catch (HttpException $e) {
            $responseBody = $e->getBody();
            $response = Json::decode($responseBody);

            $output->writeln(sprintf('<error>Error updating %s: %s</error>', $entity::class, $response->error->msg));
        }
    }

    protected function createFacet(FacetField $facetField): void
    {
        $this->facetSet->createFacetField($facetField->getField())
            ->setField($facetField->getField())
            ->setMinCount($facetField->getMinCount())
            ->setSort($facetField->getSort());

        if (!$facetField->getHasAndOr()) {
            $this->facetSet->getFacet($facetField->getField())?->getLocalParameters()->setExclude(
                $facetField->getField()
            );
        }

        $this->facets[$facetField->getField()] = $facetField; //Name is used as index
    }
}
