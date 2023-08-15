<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\Order\Grid\Massaction;

use Magento\Framework\AuthorizationInterface;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\Order\Grid\Massaction\ItemsUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemsUpdaterTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $_cfgSalesArchiveMock;

    /**
     * @var MockObject
     */
    protected $_authorizationMock;

    /**
     * @var ItemsUpdater
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_updateArgs;

    protected function setUp(): void
    {
        $this->_cfgSalesArchiveMock = $this->getMockBuilder(
            Config::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->_authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->getMock();

        $this->_model = new ItemsUpdater(
            $this->_cfgSalesArchiveMock,
            $this->_authorizationMock
        );

        $this->_updateArgs = [
            'add_order_to_archive' => ['label' => 'Move to Archive', 'url' => '*/sales_archive/massAdd'],
            'cancel_order' => ['label' => 'Cancel', 'url' => '*/sales_archive/massCancel'],
        ];
    }

    public function testConfigActive()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(true);

        $this->assertEquals($this->_updateArgs, $this->_model->update($this->_updateArgs));
    }

    public function testConfigNotActive()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(false);

        $this->assertArrayNotHasKey('add_order_to_archive', $this->_model->update($this->_updateArgs));
    }

    public function testAuthAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(true);

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->with(
            'Magento_SalesArchive::add',
            null
        )->willReturn(
            true
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        $this->assertArrayHasKey('add_order_to_archive', $updatedArgs);
    }

    public function testAuthNotAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(true);

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->with(
            'Magento_SalesArchive::add',
            null
        )->willReturn(
            false
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        $this->assertArrayNotHasKey('add_order_to_archive', $updatedArgs);
    }
}
