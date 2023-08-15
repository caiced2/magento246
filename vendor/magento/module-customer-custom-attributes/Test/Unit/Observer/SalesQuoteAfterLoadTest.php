<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Observer;

use Magento\CustomerCustomAttributes\Model\Sales\Quote;
use Magento\CustomerCustomAttributes\Model\Sales\QuoteFactory;
use Magento\CustomerCustomAttributes\Observer\SalesQuoteAfterLoad;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesQuoteAfterLoadTest extends TestCase
{
    /**
     * @var SalesQuoteAfterLoad
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $quoteFactory;

    protected function setUp(): void
    {
        $this->quoteFactory = $this->getMockBuilder(
            QuoteFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();

        $this->observer = new SalesQuoteAfterLoad($this->quoteFactory);
    }

    public function testSalesQuoteAfterLoad()
    {
        $quoteId = 1;
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel = $this->getMockBuilder(AbstractModel::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataModel->expects($this->once())->method('getId')->willReturn($quoteId);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getQuote')->willReturn($dataModel);
        $quote->expects($this->once())->method('load')->with($quoteId)->willReturnSelf();
        $quote->expects($this->once())->method('attachAttributeData')->with($dataModel)->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);
        /** @var Observer $observer */
        $this->assertInstanceOf(
            SalesQuoteAfterLoad::class,
            $this->observer->execute($observer)
        );
    }
}
