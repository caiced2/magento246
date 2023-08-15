<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Invoice\Tax;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Model\Total\Invoice\Tax\Giftwrapping;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\GiftWrapping\Model\Invoice\Tax\Giftwrapping
 */
class GiftWrappingTest extends TestCase
{
    public function testInvoiceItemTaxWrapping()
    {
        $objectHelper = new ObjectManager($this);
        $model = $objectHelper->getObject(Giftwrapping::class, []);

        $invoice = $this->getMockBuilder(
            Invoice::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getAllItems', 'getOrder', 'isLast', 'setGwItemsBaseTaxAmount', 'setGwItemsTaxAmount']
            )->getMock();

        $item = new DataObject();
        $orderItem = new DataObject(
            ['gw_id' => 1, 'gw_base_tax_amount' => 5, 'gw_tax_amount' => 10]
        );

        $item->setQty(2)->setOrderItem($orderItem);
        $order = new DataObject();

        $invoice->expects($this->any())->method('getAllItems')->willReturn([$item]);
        $invoice->expects($this->any())->method('isLast')->willReturn(true);
        $invoice->expects($this->any())->method('getOrder')->willReturn($order);
        $invoice->expects($this->once())->method('setGwItemsBaseTaxAmount')->with(10);
        $invoice->expects($this->once())->method('setGwItemsTaxAmount')->with(20);

        $model->collect($invoice);
    }
}
