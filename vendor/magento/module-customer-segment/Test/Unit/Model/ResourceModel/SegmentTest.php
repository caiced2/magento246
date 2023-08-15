<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\ResourceModel;

use Magento\Customer\Model\Config\Share;
use Magento\CustomerSegment\Model\ResourceModel\Helper;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Model\QueryResolver;
use Magento\Quote\Model\ResourceModel\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SegmentTest extends TestCase
{
    /**
     * @var Segment
     */
    protected $_resourceModel;

    /**
     * @var MockObject
     */
    protected $_resource;

    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var MockObject
     */
    protected $_configShare;

    /**
     * @var MockObject
     */
    protected $_conditions;

    /**
     * @var MockObject
     */
    protected $_segment;

    /**
     * @var MockObject
     */
    protected $queryResolverMock;

    /**
     * @var MockObject
     */
    protected $dateTimeMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['query', 'insertMultiple', 'beginTransaction', 'commit']
        );

        $this->_resource = $this->createMock(ResourceConnection::class);
        $this->_resource->expects($this->any())->method('getTableName')->willReturnArgument(0);
        $this->_resource->expects(
            $this->any()
        )->method(
            'getConnection'
        )->willReturn($this->connectionMock);

        $this->_configShare = $this->createPartialMock(
            Share::class,
            ['isGlobalScope', 'isWebsiteScope']
        );
        $this->_segment = $this->createPartialMock(
            \Magento\CustomerSegment\Model\Segment::class,
            ['getConditions', 'getWebsiteIds', 'getId']
        );

        $this->_conditions = $this->createPartialMock(
            Root::class,
            ['getConditions', 'getSatisfiedIds']
        );

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->_resource);
        $this->queryResolverMock = $this->createMock(QueryResolver::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->_resourceModel = new Segment(
            $contextMock,
            $this->createMock(Helper::class),
            $this->_configShare,
            $this->dateTimeMock,
            $this->createMock(Quote::class),
            $this->queryResolverMock
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testSaveCustomersFromSelect(): void
    {
        $select =
            $this->createPartialMock(Select::class, ['joinLeft', 'from', 'columns']);
        $this->_segment->expects($this->any())->method('getId')->willReturn(3);
        $statement = $this->createPartialMock(
            \Zend_Db_Statement::class,
            ['closeCursor', 'columnCount', 'errorCode', 'errorInfo', 'fetch', 'nextRowset', 'rowCount']
        );
        $websites = [8, 9];
        $statement
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['entity_id' => 4, 'website_id' => $websites[0]],
                ['entity_id' => 5, 'website_id' => $websites[1]],
                false
            );
        $this->connectionMock->expects(
            $this->any()
        )->method(
            'query'
        )->with(
            $select
        )->willReturn(
            $statement
        );
        $callback = function ($data) use ($websites) {
            foreach ($data as $item) {
                if (!isset($item['website_id']) || !in_array($item['website_id'], $websites)) {
                    return false;
                }
            }
            return true;
        };

        $this->connectionMock->expects(
            $this->once()
        )->method(
            'insertMultiple'
        )->with(
            'magento_customersegment_customer',
            $this->callback($callback)
        );
        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->connectionMock->expects($this->once())->method('commit');

        $this->_resourceModel->saveCustomersFromSelect($this->_segment, $select);
    }

    /**
     * @param bool $scope
     * @param array $websites
     * @param mixed $websiteIds
     *
     * @return void
     * @dataProvider aggregateMatchedCustomersDataProvider
     */
    public function testAggregateMatchedCustomers($scope, $websites, $websiteIds): void
    {
        $this->markTestSkipped('Implementation has changed');
        $nowDate = '2015-04-23 02:04:51';
        $this->dateTimeMock->expects($this->any())
            ->method('formatDate')
            ->withAnyParameters()
            ->willReturn($nowDate);

        $customerIds = [1];
        if ($scope) {
            $this->_conditions->expects($this->once())
                ->method('getSatisfiedIds')
                ->with($this->equalTo(current($websites)))
                ->willReturn($customerIds);
        } else {
            $this->_conditions->expects($this->exactly(2))
                ->method('getSatisfiedIds')
                ->withConsecutive([current($websites)], [end($websites)])
                ->willReturn($customerIds);
        }

        $this->_segment->expects($scope ? $this->once() : $this->exactly(2))
            ->method('getConditions')
            ->willReturn($this->_conditions);
        $this->_segment->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($websiteIds);
        $this->_segment->expects($this->any())
            ->method('getId')
            ->willReturn(3);
        $insertData = [
            [
                'segment_id' => 3,
                'customer_id' => 1,
                'website_id' => reset($websites),
                'added_date' => $nowDate,
                'updated_date' => $nowDate
            ],
        ];
        if (!$scope) {
            $insertData[] = [
                'segment_id' => 3,
                'customer_id' => 1,
                'website_id' => end($websites),
                'added_date' => $nowDate,
                'updated_date' => $nowDate
            ];
        }
        $this->connectionMock->expects(
            $this->once()
        )->method(
            'insertMultiple'
        )->with(
            $this->equalTo('magento_customersegment_customer'),
            $insertData
        );
        $this->connectionMock->expects($this->exactly(2))->method('beginTransaction');
        $this->connectionMock->expects($this->exactly(2))->method('commit');

        $this->_configShare->expects($this->any())->method('isGlobalScope')->willReturn($scope);
        $this->_configShare->expects($this->any())->method('isWebsiteScope')->willReturn(!$scope);
        $this->_resourceModel->aggregateMatchedCustomers($this->_segment);
    }

    /**
     * Data provider for testAggregateMatchedCustomers.
     *
     * @return array
     */
    public function aggregateMatchedCustomersDataProvider(): array
    {
        return [
            [true, [7], [7]],
            [true, [7, 9], [7]],
            [false, [7, 9], [7, 9]]
        ];
    }
}
