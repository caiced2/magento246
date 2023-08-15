<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\Order\Archive\Grid\Massaction;

use Magento\Framework\AuthorizationInterface;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\Order\Archive\Grid\Massaction\ItemsUpdater;
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
            'remove_order_from_archive' => [
                'label' => 'Move to Orders Management',
                'url' => '*/sales_archive/massRemove',
            ],
            'cancel_order' => ['label' => 'Cancel', 'url' => '*/sales_archive/massCancel'],
            'hold_order' => ['label' => 'Hold', 'url' => '*/sales_archive/massHold'],
            'unhold_order' => ['label' => 'Unhold', 'url' => '*/sales_archive/massUnhold'],
            'pdfinvoices_order' => ['label' => 'Print Invoices', 'url' => '*/sales_archive/massPrintInvoices'],
            'pdfshipments_order' => [
                'label' => 'Print Packing Slips',
                'url' => '*/sales_archive/massPrintPackingSlips',
            ],
            'pdfcreditmemos_order' => [
                'label' => 'Print Credit Memos',
                'url' => '*/sales_archive/massPrintCreditMemos',
            ],
            'pdfdocs_order' => ['label' => 'Print All', 'url' => '*/sales_archive/massPrintAllDocuments'],
            'print_shipping_label' => [
                'label' => 'Print Shipping Labels',
                'url' => '*/sales_archive/massPrintShippingLabel',
            ],
        ];
    }

    public function testConfigNotActive()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(false);

        $this->assertEquals($this->_updateArgs, $this->_model->update($this->_updateArgs));
    }

    protected function _getAclResourceMap($isAllowed)
    {
        return [
            ['Magento_Sales::cancel', null, $isAllowed],
            ['Magento_Sales::hold', null, $isAllowed],
            ['Magento_Sales::unhold', null, $isAllowed],
            ['Magento_SalesArchive::remove', null, $isAllowed]
        ];
    }

    protected function _getItemsId()
    {
        return ['cancel_order', 'hold_order', 'unhold_order', 'remove_order_from_archive'];
    }

    public function testAuthAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(true);

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->willReturnMap(
            $this->_getAclResourceMap(true)
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        foreach ($this->_getItemsId() as $massItemId) {
            $this->assertArrayHasKey($massItemId, $updatedArgs);
        }
    }

    public function testAuthNotAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->willReturn(true);

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->willReturnMap(
            $this->_getAclResourceMap(false)
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        foreach ($this->_getItemsId() as $massItemId) {
            $this->assertArrayNotHasKey($massItemId, $updatedArgs);
        }
    }
}
