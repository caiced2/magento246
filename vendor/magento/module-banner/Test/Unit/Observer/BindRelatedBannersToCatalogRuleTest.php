<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Observer;

use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Banner\Observer\BindRelatedBannersToCatalogRule;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BindRelatedBannersToCatalogRuleTest extends TestCase
{
    /**
     * @var BindRelatedBannersToCatalogRule
     */
    protected $bindRelatedBannersToCatalogRuleObserver;

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
     * @var Http|MockObject
     */
    protected $http;

    protected function setUp(): void
    {
        $this->bannerFactory = $this->getMockBuilder(\Magento\Banner\Model\ResourceModel\BannerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->bindRelatedBannersToCatalogRuleObserver = new BindRelatedBannersToCatalogRule(
            $this->bannerFactory
        );
    }

    /**
     * @param [] $banners
     *
     * @dataProvider bindRelatedBannersDataProvider
     */
    public function testBindRelatedBannersToCatalogRule($banners)
    {
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRule', 'getId'])
            ->getMock();
        $this->http = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelatedBanners', 'getId'])
            ->getMock();
        $banner = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->setMethods(['bindBannersToCatalogRule'])
            ->getMock();
        $banner->expects($this->once())->method('bindBannersToCatalogRule')->with(1, $banners)->willReturnSelf();
        $this->event->expects($this->any())->method('getRule')->willReturn($this->http);
        $this->http->expects($this->once())->method('getId')->willReturn(1);
        $this->http->expects($this->any())->method('getRelatedBanners')->willReturn(
            $banners
        );
        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->bannerFactory->expects($this->once())->method('create')->willReturn($banner);
        $this->assertInstanceOf(
            BindRelatedBannersToCatalogRule::class,
            $this->bindRelatedBannersToCatalogRuleObserver->execute($this->eventObserver)
        );
    }

    public function bindRelatedBannersDataProvider()
    {
        return [
            [
                [],
            ],
            [
                'banner1',
                'banner2'
            ]
        ];
    }
}
