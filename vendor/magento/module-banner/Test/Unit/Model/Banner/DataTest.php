<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model\Banner;

use Magento\Banner\Model\Banner\Data;
use Magento\Banner\Model\Config;
use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Checkout\Model\Session;
use Magento\Cms\Model\Template\Filter;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Http\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var int
     */
    const STORE_ID = 1;

    /**
     * @var Data
     */
    private $unit;

    /**
     * @var MockObject
     */
    private $bannerResource;

    /**
     * @var MockObject
     */
    private $checkoutSession;

    /**
     * @var MockObject
     */
    private $httpContext;

    /**
     * @var MockObject
     */
    private $currentWebsite;

    /**
     * @var MockObject
     */
    protected $banner;

    /**
     * @var MockObject
     */
    private $connectionMock;

    protected function setUp(): void
    {
        $this->bannerResource = $this->createMock(Banner::class);
        $this->checkoutSession = $this->createPartialMock(
            Session::class,
            ['getQuoteId', 'getQuote']
        );
        $this->httpContext = $this->createMock(Context::class);
        $this->currentWebsite = $this->createMock(Website::class);
        $this->banner = $this->createMock(\Magento\Banner\Model\Banner::class);

        $pageFilterMock = $this->createMock(Filter::class);
        $pageFilterMock->expects($this->any())->method('filter')->willReturnArgument(0);
        $filterProviderMock = $this->createMock(FilterProvider::class);
        $filterProviderMock->expects($this->any())->method('getPageFilter')->willReturn($pageFilterMock);

        $currentStore = $this->createMock(Store::class);
        $currentStore->expects($this->any())->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $storeManager->expects($this->once())->method('getStore')->willReturn($currentStore);
        $storeManager->expects($this->any())->method('getWebsite')->willReturn($this->currentWebsite);
        $selectMock = $this->createMock(Select::class);
        $selectMock->expects($this->any())->method('from')->willReturnSelf();
        $selectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchCol', 'select'])
            ->getMockForAbstractClass();
        $this->connectionMock->expects($this->any())->method('select')->willReturn($selectMock);

        $this->bannerResource->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            Data::class,
            [
                'banner' => $this->banner,
                'bannerResource' => $this->bannerResource,
                'checkoutSession' => $this->checkoutSession,
                'httpContext' => $this->httpContext,
                'filterProvider' => $filterProviderMock,
                'storeManager' => $storeManager,
            ]
        );
    }

    /**
     * @param array $result
     * @return array
     */
    protected function getExpectedResult($result)
    {
        return [
            'items' => $result + [
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => [],
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => [],
                Config::BANNER_WIDGET_DISPLAY_FIXED => [],
            ],
            'store_id' => self::STORE_ID
        ];
    }

    public function testGetBannersContentFixed()
    {
        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')->willReturn([]);
        $this->connectionMock->expects($this->once())->method('fetchCol')
            ->willReturn([123]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('Fixed Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_FIXED => [
                    123 => [
                        'content' => 'Fixed Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }

    public function testGetBannersContentCatalogRule()
    {
        $this->httpContext->expects($this->any())->method('getValue')->willReturn('customer_group');
        $this->currentWebsite->expects($this->any())->method('getId')->willReturn('website_id');

        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->willReturn([]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')
            ->with('website_id', 'customer_group')->willReturn([123]);
        $this->connectionMock->expects($this->once())->method('fetchCol')
            ->willReturn([]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('CatalogRule Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => [
                    123 => [
                        'content' => 'CatalogRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
                Config::BANNER_WIDGET_DISPLAY_FIXED => [
                    123 => [
                        'content' => 'CatalogRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }

    public function testGetBannersContentSalesRule()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getAppliedRuleIds'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('getAppliedRuleIds')->willReturn('15,11,12');
        $this->checkoutSession->expects($this->once())->method('getQuoteId')->willReturn(8000);
        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn($quote);

        $this->bannerResource->expects($this->once())->method('getSalesRuleRelatedBannerIds')->with([15, 11, 12])
            ->willReturn([123]);
        $this->bannerResource->expects($this->once())->method('getCatalogRuleRelatedBannerIds')->willReturn([]);
        $this->connectionMock->expects($this->once())->method('fetchCol')
            ->willReturn([]);

        $this->bannerResource->expects($this->any())->method('getStoreContent')
            ->with(123, self::STORE_ID)->willReturn('SalesRule Dynamic Block 123');
        $this->banner->expects($this->any())->method('load')->with(123)->willReturnSelf();
        $this->banner->expects($this->any())->method('getTypes')->willReturn('footer');

        $this->assertEquals(
            $this->getExpectedResult([
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => [
                    123 => [
                        'content' => 'SalesRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
                Config::BANNER_WIDGET_DISPLAY_FIXED => [
                    123 => [
                        'content' => 'SalesRule Dynamic Block 123', 'types' => 'footer', 'id' => 123
                    ],
                ],
            ]),
            $this->unit->getSectionData()
        );
    }
}
