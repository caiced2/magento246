<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Model\ResourceModel\Item\Collection;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\MultipleWishlist\Model\ResourceModel\Item\Collection\Updater;
use Magento\Wishlist\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdaterTest extends TestCase
{
    /**
     * @var Updater|MockObject
     */
    protected $model;

    /** @var Data|MockObject */
    protected $wishlistHelper;

    protected function setUp(): void
    {
        $this->wishlistHelper = $this->createMock(Data::class);
        $this->model = new Updater($this->wishlistHelper);
    }

    public function testUpdate()
    {
        $connectionMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false
        );
        $select = $this->createMock(Select::class);
        $argument = $this->createMock(AbstractDb::class);
        $argument->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->wishlistHelper->expects($this->once())
            ->method('getDefaultWishlistName')
            ->willReturn('Default Wish List');
        $argument->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);
        $select->expects($this->once())
            ->method('columns')
            ->with(['wishlist_name' => "IFNULL(wishlist.name, 'Default Wish List')"]);
        $connectionMock->expects($this->atLeastOnce())
            ->method('getIfNullSql')
            ->with('wishlist.name', 'Default Wish List')
            ->willReturn("IFNULL(wishlist.name, 'Default Wish List')");
        $argument->expects($this->once())
            ->method('addFilterToMap')
            ->with('wishlist_name', "IFNULL(wishlist.name, 'Default Wish List')");
        $connectionMock->expects($this->atLeastOnce())
            ->method('quote')
            ->with('Default Wish List')
            ->willReturn('Default Wish List');

        $this->assertSame($argument, $this->model->update($argument));
    }
}
