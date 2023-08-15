<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\PeriodSync;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 */
class EntitySynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var int
     */
    private $currentVersionId;

    /**
     * @var ReadEntityVersion
     */
    private $entityVersionReader;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @var EntityStaging
     */
    private $entityStaging;

    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @var UpdateInterface[]
     */
    private $updates = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->updateRepository = $this->objectManager->get(UpdateRepositoryInterface::class);
        $this->versionManager = $this->objectManager->get(VersionManager::class);
        $this->currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->entityVersionReader = $this->objectManager->get(ReadEntityVersion::class);
        $this->typeResolver = $this->objectManager->get(TypeResolver::class);
        $this->retrieverPool = $this->objectManager->get(RetrieverPool::class);
        $this->entityStaging = $this->objectManager->get(EntityStaging::class);
        $consumerFactory = $this->objectManager->get(ConsumerFactory::class);
        $this->consumer = $consumerFactory->get('staging.synchronize_entity_period');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->versionManager->setCurrentVersionId($this->currentVersionId);
        foreach ($this->updates as $update) {
            try {
                $this->updateRepository->delete($update);
            } catch (\Exception $e) {
                //entity is already deleted
            }
        }
        $this->updates = [];
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/pages.php
     */
    public function testExecuteCmsPage()
    {
        $pageIdentifier = 'page100';

        $filter = $this->objectManager->create(Filter::class);
        $filter->setField('identifier')->setValue($pageIdentifier);
        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setFilters([$filter]);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $pageRepository = $this->objectManager->get(PageRepositoryInterface::class);
        $pageSearchResults = $pageRepository->getList($searchCriteria);
        $pages = $pageSearchResults->getItems();
        /** @var \Magento\Cms\Api\Data\PageInterface $page */
        $page = \array_values($pages)[0];
        $entityType = $this->typeResolver->resolve($page);
        $this->checkSynchronize($entityType, (int) $page->getId());
    }

    /**
     * @magentoDataFixture Magento/Cms/_files/block.php
     */
    public function testExecuteCmsBlock()
    {
        $blockIdentifier = 'fixture_block';

        $filter = $this->objectManager->create(Filter::class);
        $filter->setField('identifier')->setValue($blockIdentifier);
        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setFilters([$filter]);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $blockRepository = $this->objectManager->get(BlockRepositoryInterface::class);
        $blockSearchResults = $blockRepository->getList($searchCriteria);
        $blocks = $blockSearchResults->getItems();
        /** @var \Magento\Cms\Api\Data\BlockInterface $block */
        $block = \array_values($blocks)[0];
        $entityType = $this->typeResolver->resolve($block);
        $this->checkSynchronize($entityType, (int) $block->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     */
    public function testExecuteProduct()
    {
        $productSku = 'simple2';

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku);
        $entityType = $this->typeResolver->resolve($product);
        $this->checkSynchronize($entityType, (int) $product->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/category.php
     */
    public function testExecuteCatalogCategory()
    {
        $categoryId = 333;

        $categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $category = $categoryRepository->get($categoryId);
        $entityType = $this->typeResolver->resolve($category);
        $this->checkSynchronize($entityType, (int) $category->getId());
    }

    /**
     * @magentoDataFixture Magento/SalesRule/_files/rule_specific_date.php
     */
    public function testExecuteSalesRule()
    {
        $salesRuleName = '#1';

        $filterGroup = $this->objectManager->create(FilterGroup::class);
        $filterGroup->setData('name', $salesRuleName);
        $searchCriteria = $this->objectManager->create(SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups([$filterGroup]);
        $salesRuleRepository = $this->objectManager->get(RuleRepositoryInterface::class);
        $salesRuleSearchResult = $salesRuleRepository->getList($searchCriteria);
        $salesRules = $salesRuleSearchResult->getItems();
        /** @var \Magento\SalesRule\Api\Data\RuleInterface $salesRule */
        $salesRule = \array_values($salesRules)[0];
        $entityType = $this->typeResolver->resolve($salesRule);
        $this->checkSynchronize($entityType, (int) $salesRule->getRuleId());
    }

    /**
     * @magentoDataFixture Magento/CatalogRule/_files/rule_by_category_ids.php
     */
    public function testExecuteCatalogRule()
    {
        $catalogRuleName = 'test_category_rule';

        $catalogRuleCollection = $this->objectManager->create(CatalogRuleCollection::class);
        $catalogRuleCollection->addFilter('name', $catalogRuleName);
        $catalogRuleCollection->load();
        /** @var \Magento\CatalogRule\Model\Rule $catalogRule */
        $catalogRule = $catalogRuleCollection->getFirstItem();
        $entityType = $this->typeResolver->resolve($catalogRule);
        $this->checkSynchronize($entityType, (int) $catalogRule->getId());
    }

    /**
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return UpdateInterface
     */
    private function createUpdate(int $startTimestamp, int $endTimestamp): UpdateInterface
    {
        $update = $this->objectManager->create(UpdateInterface::class);
        $update->setName('Update ' . $startTimestamp);
        $update->setStartTime(date(DATE_ATOM, $startTimestamp));
        $update->setEndTime(date(DATE_ATOM, $endTimestamp));
        $this->updateRepository->save($update);
        $this->updates[] = $update;

        return $update;
    }

    /**
     * @param UpdateInterface $update
     * @param object $entity
     * @return void
     */
    private function schedule(UpdateInterface $update, $entity): void
    {
        $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        $this->versionManager->setCurrentVersionId($update->getId());
        $this->entityStaging->schedule($entity, $update->getId());
        $this->versionManager->setCurrentVersionId($currentVersionId);
    }

    /**
     * @param string $entityType
     * @param int $entityId
     */
    private function checkSynchronize(string $entityType, int $entityId): void
    {
        $retriever = $this->retrieverPool->getRetriever($entityType);
        $entity = $retriever->getEntity($entityId);
        $dummyUpdate = $this->createUpdate(strtotime('+1 day'), strtotime('+3 days'));
        $this->schedule($dummyUpdate, $entity);

        $update = $this->createUpdate(strtotime('+4 days'), strtotime('+6 days'));
        $this->schedule($update, $entity);
        $origUpdateId = $update->getId();
        $update->setStartTime(date(DATE_ATOM, strtotime('+2 days')));
        $this->updateRepository->save($update);
        $origUpdate = $this->updateRepository->get($origUpdateId);
        $this->assertEquals($update->getId(), $origUpdate->getMovedTo());
        $this->consumer->process(1);
        $update = $this->updateRepository->get($origUpdateId);
        $this->assertEmpty($update->getMovedTo());

        $update->setStartTime(date(DATE_ATOM, strtotime('+5 days')));
        $update->setEndTime(date(DATE_ATOM, strtotime('+7 days')));
        $this->updateRepository->save($update);
        $this->consumer->process(2);
        $this->versionManager->setCurrentVersionId($update->getId());
        $rowId = $this->entityVersionReader->getCurrentVersionRowId($entityType, $entityId);
        $this->assertNotEmpty($rowId, "{$entityType} staging synchronization failed.");
        $nextVersionId = $this->entityVersionReader->getNextVersionId($entityType, $update->getId(), $entityId);
        $this->assertEquals($update->getRollbackId(), $nextVersionId);

        $update->setEndTime(null);
        $this->updateRepository->save($update);
        $this->consumer->process(1);
        $nextVersionId = $this->entityVersionReader->getNextVersionId($entityType, $update->getId(), $entityId);
        $this->assertEquals(VersionManager::MAX_VERSION, $nextVersionId);

        $update->setEndTime(date(DATE_ATOM, strtotime('+6 days')));
        $this->updateRepository->save($update);
        $this->consumer->process(1);
        $nextVersionId = $this->entityVersionReader->getNextVersionId($entityType, $update->getId(), $entityId);
        $this->assertEquals($update->getRollbackId(), $nextVersionId);
    }
}
