<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Model\ResourceModel\Item\Report;

use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\DataObject\Copy\Config;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MultipleWishlist\Model\ResourceModel\Item\Report\Collection;
use Magento\Quote\Model\ResourceModel\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var MockObject
     */
    protected $resourceMock;

    /**
     * @var MockObject
     */
    protected $customerResourceMock;

    /**
     * @var MockObject
     */
    protected $selectMock;

    /**
     * Test method _addCustomerInfo throw constructor
     */
    public function testAddCustomerInfo()
    {
        $joinCustomerData = ['customer' => 'customer_entity'];
        $joinCustomerMap = 'customer.entity_id = wishlist_table.customer_id';

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock->expects($this->any())
            ->method('from')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('reset')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('join')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with($joinCustomerData, $joinCustomerMap, [])
            ->willReturnSelf();

        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->method('getConcatSql')->willReturn('test-sql');
        $this->selectMock->expects($this->any())
            ->method('getConnection')
            ->withAnyParameters()
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->resourceMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getMainTable')
            ->willReturn('test_table');
        $this->resourceMock->expects($this->any())
            ->method('getTable')
            ->willReturn('customer_entity');

        $this->customerResourceMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldsetConfigmock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldsetConfigmock->expects($this->once())
            ->method('getFieldset')
            ->with('customer_account')
            ->willReturn([]);

        $helper = new ObjectManager($this);
        $this->model = $helper->getObject(
            Collection::class,
            [
                'customerResource' => $this->customerResourceMock,
                'resource' => $this->resourceMock,
                'fieldsetConfig' => $fieldsetConfigmock,
            ]
        );
    }
}
