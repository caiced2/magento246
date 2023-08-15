<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftRegistry\Model\Plugin\OrderAddressName as OrderAddressNamePlugin;
use Magento\Sales\Model\Order\Address as OrderAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderAddressNameTest extends TestCase
{
    /**
     * @var OrderAddressNamePlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderAddress|MockObject
     */
    private $subjectMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(OrderAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftregistryItemId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(OrderAddressNamePlugin::class);
    }

    public function testAfterGetName()
    {
        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn(1);

        $this->assertEquals(
            __('Ship to the recipient\'s address.'),
            $this->plugin->afterGetName($this->subjectMock, 'Result')
        );
    }

    public function testAfterGetNameNotGiftRegistry()
    {
        $result = 'result';

        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn(null);

        $this->assertEquals($result, $this->plugin->afterGetName($this->subjectMock, $result));
    }
}
