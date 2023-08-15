<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Checkout\Block\Cart\Shipping;

use Magento\Checkout\Model\Cart\CollectQuote;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\CustomerSegment\Model\Checkout\Block\Cart\Shipping\Plugin as ShippingPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\EstimateAddressInterfaceFactory;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var ShippingPlugin
     */
    private $plugin;

    /**
     * @var EstimateAddressInterfaceFactory|MockObject
     */
    private $addressFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var CollectQuote|MockObject
     */
    private $collectQuote;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->addressFactory = $this->getMockBuilder(EstimateAddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectQuote = $this->getMockBuilder(CollectQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = $objectManager->getObject(
            ShippingPlugin::class,
            [
                'addressFactory' => $this->addressFactory,
                'quoteRepository' => $this->quoteRepository,
                'customerSession' => $this->customerSession,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    public function testBeforeCollect()
    {
        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertSame(
            [$this->quote],
            $this->plugin->beforeCollect($this->collectQuote, $this->quote)
        );
    }
}
