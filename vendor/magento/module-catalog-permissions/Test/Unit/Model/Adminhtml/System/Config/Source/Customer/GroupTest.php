<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Adminhtml\System\Config\Source\Customer;

use Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Customer\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogPermissions\Model\Adminhtml\System\Config\Source\Customer\Group
 */
class GroupTest extends TestCase
{
    /**
     * @var Group
     */
    protected $group;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );

        $this->group = new Group($this->collectionFactoryMock);
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock
            ->expects($this->once())
            ->method('loadData')
            ->willReturnSelf();
        $collectionMock
            ->expects($this->once())
            ->method('toOptionArray')
            ->willReturn(['foo', 'bar']);

        $this->assertEquals(['foo', 'bar'], $this->group->toOptionArray());
    }
}
