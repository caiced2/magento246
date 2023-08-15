<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Block\Customer;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\GiftRegistry\Block\Customer\ListCustomer;
use Magento\GiftRegistry\Model\Entity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListCustomerTest extends TestCase
{
    /**
     * @var ListCustomer
     */
    protected $block;

    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $localeDateMock;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->contextMock = $this->createMock(Context::class);
        $this->localeDateMock = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->contextMock
            ->expects($this->any())
            ->method('getLocaleDate')
            ->willReturn($this->localeDateMock);
        $this->block = $helper->getObject(
            ListCustomer::class,
            ['context' => $this->contextMock]
        );
    }

    public function testGetFormattedDate()
    {
        $date = '07/24/14';
        $itemMock = $this->getMockBuilder(Entity::class)
            ->addMethods(['getCreatedAt'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->once())->method('getCreatedAt')->willReturn($date);
        $this->localeDateMock
            ->expects($this->once())
            ->method('formatDateTime')
            ->with(new \DateTime($date), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE)
            ->willReturn($date);
        $this->assertEquals($date, $this->block->getFormattedDate($itemMock));
    }
}
