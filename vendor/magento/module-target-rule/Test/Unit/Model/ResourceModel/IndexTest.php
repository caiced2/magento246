<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\CustomerSegment\Helper\Data as CustomerSegmentData;
use Magento\CustomerSegment\Model\Customer;
use Magento\CustomerSegment\Model\ResourceModel\Segment as CustomerSegmentModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Helper\Data as TargetRuleData;
use Magento\TargetRule\Model\ResourceModel\Index as TargetRuleIndex;
use Magento\TargetRule\Model\ResourceModel\IndexPool;
use Magento\TargetRule\Model\ResourceModel\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\TargetRule\Model\ResourceModel\Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * @var TargetRuleIndex
     */
    private $model;

    /**
     * @var AdapterInterface|MockObject
     */
    private $adapterInterface;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(DbContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexPoolMock = $this->getMockBuilder(IndexPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();
        $segmentCollectionFactoryMock = $this->getMockBuilder(CustomerSegmentModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionFactoryMock = $this->getMockBuilder(CatalogCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $visibilityMock = $this->getMockBuilder(ProductVisibility::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customerSegmentDataMock = $this->getMockBuilder(CustomerSegmentData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $targetRuleDataMock = $this->getMockBuilder(TargetRuleData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stockHelperMock = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);

        $contextMock->expects($this->any())
            ->method('getResources')
            ->willReturn($resourceMock);

        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            TargetRuleIndex::class,
            [
                'context' => $contextMock,
                'indexPool' => $indexPoolMock,
                'ruleMock' => $ruleMock,
                'segmentCollectionFactory' => $segmentCollectionFactoryMock,
                'productCollectionFactory' => $productCollectionFactoryMock,
                'storeManager' => $storeManagerMock,
                'visibility' => $visibilityMock,
                'customer' => $customerMock,
                'session' => $sessionMock,
                'customerSegmentData' => $customerSegmentDataMock,
                'targetRuleData' => $targetRuleDataMock,
                'coreRegistry' => $this->getMockBuilder(Registry::class)
                    ->disableOriginalConstructor()
                    ->getMock(),
                'stockHelper' => $stockHelperMock,
            ]
        );
    }

    /**
     * @return array
     */
    public function getOperatorConditionDataProvider(): array
    {
        return [
            ['category_id', '()', ' IN(?)', [4], [4]],
            ['category_id', '!()', ' NOT IN(?)', [4], [4]],
            ['category_id', '{}', ' IN (?)', [5], [5]],
            ['category_id', '!{}', ' NOT IN (?)', [5], [5]],
            ['category_id', '{}', ' LIKE ?', 5, '%5%'],
            ['category_id', '!{}', ' NOT LIKE ?', 5, '%5%'],
            ['category_id', '>=', '>=?', 5, 5],
            ['category_id', '==', '=?', 7, 7],
            ['value', '{}', ' IN (?)', [6], [6]],
            ['value', '!{}', ' NOT IN (?)', [6], [6]],
            ['value', '{}', ' LIKE ?', 6, '%6%'],
            ['value', '!{}', ' NOT LIKE ?', 6, '%6%'],
        ];
    }

    /**
     * @param string $field
     * @param string $operator
     * @param string $expectedSelectOperator
     * @param mixed $value
     * @param mixed $expectedValue
     *
     * @dataProvider getOperatorConditionDataProvider
     *
     * @return void
     */
    public function testGetOperatorCondition(
        string $field,
        string $operator,
        string $expectedSelectOperator,
        $value,
        $expectedValue
    ): void {
        $quoteIdentifier = '`' . $field . '`';
        $this->adapterInterface->expects($this->once())
            ->method('quoteIdentifier')
            ->willReturn($quoteIdentifier);
        $this->adapterInterface->expects($this->once())
            ->method('quoteInto')
            ->with($quoteIdentifier . $expectedSelectOperator, $expectedValue);

        $this->model->getOperatorCondition($field, $operator, $value);
    }
}
