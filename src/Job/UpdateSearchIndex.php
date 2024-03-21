<?php

declare(strict_types=1);

namespace Jield\Search\Job;

use Doctrine\ORM\EntityManager;
use Jield\Search\Service\AbstractSearchService;
use Psr\Container\ContainerInterface;
use SlmQueue\Job\AbstractJob;
use Symfony\Component\Console\Output\BufferedOutput;

final class UpdateSearchIndex extends AbstractJob
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManager      $entityManager
    )
    {
    }

    public function execute(): ?int
    {
        $entityClassName = $this->getContent()['entityClassName'];
        $searchService   = $this->getContent()['searchService'];

        //I have to tell the system which search service we have to take (to have the correct connection)
        /** @var AbstractSearchService $searchServiceInstance */
        $searchServiceInstance = $this->container->get($searchService);
        $output                = new BufferedOutput();

        $searchServiceInstance->updateCollection(output: $output, entity: new $entityClassName());

        return null;
    }
}
