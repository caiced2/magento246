<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\FailedItemProcessor;
use Magento\AdvancedCheckout\Model\Observer\CollectTotalsFailedItems;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectTotalsFailedItemsTest extends TestCase
{
    /**
     * @var CollectTotalsFailedItems
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $cartMock;

    /**
     * @var MockObject
     */
    protected $itemProcessorMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(Cart::class);
        $this->itemProcessorMock =
            $this->createMock(FailedItemProcessor::class);
        $this->observerMock = $this->createMock(Observer::class);

        $this->model = new CollectTotalsFailedItems(
            $this->cartMock,
            $this->itemProcessorMock
        );
    }

    public function testExecuteWithEmptyAffectedItems()
    {
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn([]);
        $this->itemProcessorMock->expects($this->never())->method('process');

        $this->model->execute($this->observerMock);
    }

    public function testExecuteWithNonEmptyAffectedItems()
    {
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn(['not empty']);
        $this->itemProcessorMock->expects($this->once())->method('process');

        $this->model->execute($this->observerMock);
    }
}
