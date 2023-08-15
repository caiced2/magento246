<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Observer;

use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Banner\Observer\BindRelatedBannersToSalesRule;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\SalesRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BindRelatedBannersToSalesRuleTest extends TestCase
{
    /**
     * @var BindRelatedBannersToSalesRule
     */
    protected $bindRelatedBannersToSalesRuleObserver;

    /**
     * @var Observer
     */
    protected $eventObserver;

    /**
     * @var \Magento\Banner\Model\ResourceModel\BannerFactory|MockObject
     */
    protected $bannerFactory;

    /**
     * @var Event|MockObject
     */
    protected $event;

    /**
     * @var Rule|MockObject
     */
    protected $salesRule;

    protected function setUp(): void
    {
        $this->bannerFactory = $this->getMockBuilder(\Magento\Banner\Model\ResourceModel\BannerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->bindRelatedBannersToSalesRuleObserver = new BindRelatedBannersToSalesRule(
            $this->bannerFactory
        );
    }

    /**
     * @param array|null $banners
     * @param bool $isNeedToBind
     *
     * @dataProvider bindRelatedBannersDataProvider
     */
    public function testBindRelatedBannersToSalesRule($banners, $isNeedToBind)
    {
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRule', 'getId'])
            ->getMock();
        $this->salesRule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelatedBanners', 'getId'])
            ->getMock();
        $banner = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->setMethods(['bindBannersToSalesRule'])
            ->getMock();
        if ($isNeedToBind) {
            $banner->expects($this->once())->method('bindBannersToSalesRule')->with(1, $banners)->willReturnSelf();
            $this->bannerFactory->expects($this->once())->method('create')->willReturn($banner);
            $this->salesRule->expects($this->once())->method('getId')->willReturn(1);
            $this->salesRule->expects($this->any())->method('getRelatedBanners')->willReturn(
                $banners
            );
        } else {
            $banner->expects($this->never())->method('bindBannersToSalesRule');
            $this->bannerFactory->expects($this->never())->method('create')->willReturn($banner);
            $this->salesRule->expects($this->never())->method('getId');
        }
        $this->event->expects($this->any())->method('getRule')->willReturn($this->salesRule);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->any())->method('getEvent')->willReturn($this->event);

        $this->assertInstanceOf(
            BindRelatedBannersToSalesRule::class,
            $this->bindRelatedBannersToSalesRuleObserver->execute($this->eventObserver)
        );
    }

    public function bindRelatedBannersDataProvider()
    {
        return [
            [
                [],
                true
            ],
            [
                ['banner1', 'banner2'],
                true
            ],
            [
                null,
                false
            ],
            [
                'string',
                false
            ]
        ];
    }
}
