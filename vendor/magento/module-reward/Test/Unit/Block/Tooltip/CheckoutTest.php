<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Tooltip;

use Magento\Customer\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Reward\Block\Tooltip;
use Magento\Reward\Block\Tooltip\Checkout;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Action\AbstractAction;
use Magento\Reward\Model\Action\Salesrule;
use Magento\Reward\Model\Reward;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    public function testPrepareLayout()
    {
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rewardAction = $this->getMockBuilder(
            AbstractAction::class
        )->disableOriginalConstructor()
            ->getMock();
        $rewardHelper = $this->getMockBuilder(
            Data::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['isEnabledOnFront']
            )->getMock();
        $customerSession = $this->getMockBuilder(
            Session::class
        )->disableOriginalConstructor()
            ->getMock();
        $rewardInstance = $this->getMockBuilder(
            Reward::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['setWebsiteId', 'setCustomer', 'getActionInstance']
            )->getMock();
        $storeManager = $this->getMockBuilder(
            StoreManager::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getStore', 'getWebsiteId']
            )->getMock();

        $objectManager = new ObjectManager($this);

        /** @var Tooltip $block */
        $block = $objectManager->getObject(
            Checkout::class,
            [
                'data' => ['reward_type' => Salesrule::class],
                'customerSession' => $customerSession,
                'rewardHelper' => $rewardHelper,
                'rewardInstance' => $rewardInstance,
                'storeManager' => $storeManager
            ]
        );
        $layout = $this->createMock(Layout::class);

        $rewardHelper->expects($this->any())->method('isEnabledOnFront')->willReturn(true);

        $storeManager->expects($this->any())->method('getStore')->willReturn($store);
        $storeManager->getStore()->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $rewardInstance->expects($this->any())->method('setCustomer')->willReturn($rewardInstance);
        $rewardInstance->expects($this->any())->method('setWebsiteId')->willReturn($rewardInstance);
        $rewardInstance->expects(
            $this->any()
        )->method(
            'getActionInstance'
        )->with(
            Salesrule::class
        )->willReturn(
            $rewardAction
        );

        $object = $block->setLayout($layout);
        $this->assertEquals($block, $object);
    }
}
