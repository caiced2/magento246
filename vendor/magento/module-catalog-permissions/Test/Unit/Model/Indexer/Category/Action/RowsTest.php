<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Category\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Index;
use Magento\CatalogPermissions\Model\Indexer\AbstractAction;
use Magento\CatalogPermissions\Model\Indexer\Category\Action\Rows;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\CustomerGroupFilter;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for category rows indexer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RowsTest extends TestCase
{
    /**
     * @var Rows
     */
    private $rows;

    /**
     * @var CustomerGroupFilter|MockObject
     */
    private $customerGroupFilter;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Index|MockObject
     */
    private $helper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $resource = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $resource->method('getConnection')
            ->willReturn($this->connectionMock);
        $resource->method('getTableName')->willReturn(AbstractAction::INDEX_TABLE);
        $websiteCollectionFactory = $this->createMock(WebsiteCollectionFactory::class);
        $groupCollectionFactory = $this->createMock(GroupCollectionFactory::class);
        $config = $this->createMock(ConfigInterface::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([]);
        $catalogConfig = $this->createMock(CatalogConfig::class);
        $coreCache = $this->createMock(CacheInterface::class);
        $metadataPool = $this->createMock(MetadataPool::class);
        $this->helper = $this->createMock(Index::class);
        $this->customerGroupFilter = $this->createMock(CustomerGroupFilter::class);
        $tableMaintainer = $this->createMock(TableMaintainer::class);
        $tableMaintainer->method('getMode')->willReturn(ModeSwitcher::DIMENSION_CUSTOMER_GROUP);
        $this->rows = new Rows(
            $resource,
            $websiteCollectionFactory,
            $groupCollectionFactory,
            $config,
            $storeManager,
            $catalogConfig,
            $coreCache,
            $metadataPool,
            $this->helper,
            null,
            null,
            $this->customerGroupFilter,
            null,
            $tableMaintainer
        );
    }

    /**
     * Test for execute rows.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $categoryIds = [1, 2];
        $groupIds = [3, 4];
        $this->customerGroupFilter->method('getGroupIds')
            ->willReturn($groupIds);
        $this->connectionMock->method('beginTransaction')
            ->willReturnSelf();
        $this->connectionMock->method('commit')
            ->willReturnSelf();
        $this->connectionMock
            ->method('delete')
            ->withConsecutive(
                [AbstractAction::INDEX_TABLE, 'customer_group_id IN (3,4) AND category_id IN (1,2)'],
                [AbstractAction::INDEX_TABLE . '_product', 'customer_group_id IN (3,4) AND product_id IN (1,2)']
            );
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->method('from')
            ->willReturnSelf();
        $selectMock->method('where')
            ->willReturnSelf();
        $this->connectionMock->method('fetchAll')
            ->willReturn([]);
        $this->connectionMock->method('select')
            ->willReturn($selectMock);
        $this->helper->method('getCategoryList')
            ->willReturn([]);
        $this->helper->method('getChildCategories')->willReturn([]);
        $this->helper->method('getProductList')->willReturn([1,2]);
        $this->rows->execute($categoryIds);
    }
}
