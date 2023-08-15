<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Customer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Block\Customer\AccountLink;
use Magento\Reward\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountLinkTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $_objectManagerHelper;

    protected function setUp(): void
    {
        $this->_objectManagerHelper = new ObjectManager($this);
    }

    public function testToHtml()
    {
        /** @var Data|MockObject $helper */
        $helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AccountLink $block */
        $block = $this->_objectManagerHelper->getObject(
            AccountLink::class,
            ['rewardHelper' => $helper]
        );

        $helper->expects($this->once())->method('isEnabledOnFront')->willReturn(false);

        $this->assertEquals('', $block->toHtml());
    }
}
