<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Customer;

use Magento\AdvancedCheckout\Block\Customer\Link as CustomerLink;
use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $_objectManagerHelper;

    protected function setUp(): void
    {
        $this->_objectManagerHelper = new ObjectManager($this);
    }

    public function testToHtml()
    {
        /** @var Data|MockObject $customerHelper */
        $customerHelper = $this->getMockBuilder(
            Data::class
        )->disableOriginalConstructor()
            ->getMock();

        /** @var \Magento\Invitation\Block\Link $block */
        $block = $this->_objectManagerHelper->getObject(
            CustomerLink::class,
            ['customerHelper' => $customerHelper]
        );

        $customerHelper->expects($this->once())->method('isSkuApplied')->willReturn(false);

        $this->assertEquals('', $block->toHtml());
    }
}
