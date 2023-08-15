<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update;

use Laminas\Uri\Uri as UriHandler;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Block\Adminhtml\Update\Preview;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Preview block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewTest extends TestCase
{
    private const STUB_HOST = 'example.com';
    private const STUB_WEBSITE_1_NAME = 'Website 1';
    private const STUB_WEBSITE_1_ID = 1;
    private const STUB_STORE_1_NAME = 'Store 1';

    private const STUB_STORE_VIEW_EN_NAME = 'EN Store View';
    private const STUB_STORE_VIEW_EN_CODE = 'store_view_en';
    private const STUB_STORE_VIEW_EN_URL = 'http://' . self::STUB_HOST . '/en';

    private const STUB_STORE_VIEW_FR_NAME = 'FR Store View';
    private const STUB_STORE_VIEW_FR_CODE = 'store_view_fr';
    private const STUB_STORE_VIEW_FR_URL = 'http://' . self::STUB_HOST . '/fr';

    /**
     * @var Preview
     */
    private $block;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var Escaper|MockObject
     */
    private $escaperMock;

    /**
     * @var UriHandler|MockObject
     */
    private $uriHandlerMock;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->escaperMock = $this->createMock(Escaper::class);
        $this->uriHandlerMock = $this->createMock(UriHandler::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getServer'])
            ->getMockForAbstractClass();

        $this->block = $objectManager->getObject(
            Preview::class,
            [
                '_storeManager' => $this->storeManagerMock,
                '_request' => $this->requestMock,
                '_escaper' => $this->escaperMock,
                'uriHandler' => $this->uriHandlerMock,
            ]
        );
    }

    /**
     * Testing the store selector with enabled and disabled store views
     *
     * @dataProvider storeViewsDataProvider
     *
     * @param array $storeViews
     * @param array $expectedResult
     */
    public function testGetStoreSelectorOptionsForDifferentStoreViews(array $storeViews, array $expectedResult): void
    {
        $websiteMock = $this->getMockBuilder(Website::class)->disableOriginalConstructor()
            ->setMethods(['getGroups', 'getId', 'getName'])->getMock();
        $groupMock = $this->getMockBuilder(Group::class)->disableOriginalConstructor()->setMethods([])
            ->getMock();

        $this->requestMock->expects($this->any())->method('getServer')->with('HTTP_HOST')
            ->willReturn(self::STUB_HOST);
        $this->uriHandlerMock->expects($this->any())->method('parse')->willReturnSelf();
        $this->uriHandlerMock->expects($this->any())->method('getHost')->willReturn(self::STUB_HOST);
        $this->uriHandlerMock->expects($this->any())->method('getPort')->willReturn(null);
        $this->escaperMock->expects($this->any())->method('escapeHtml')->willReturnMap(
            [
                [self::STUB_WEBSITE_1_NAME, null, self::STUB_WEBSITE_1_NAME],
                [self::STUB_STORE_1_NAME, null, self::STUB_STORE_1_NAME],
                [self::STUB_STORE_VIEW_EN_NAME, null, self::STUB_STORE_VIEW_EN_NAME],
                [self::STUB_STORE_VIEW_EN_CODE, null, self::STUB_STORE_VIEW_EN_CODE],
                [self::STUB_STORE_VIEW_FR_NAME, null, self::STUB_STORE_VIEW_FR_NAME],
                [self::STUB_STORE_VIEW_FR_CODE, null, self::STUB_STORE_VIEW_FR_CODE],
            ]
        );

        $websiteMock->expects($this->any())->method('getId')->willReturn(self::STUB_WEBSITE_1_ID);
        $websiteMock->expects($this->any())->method('getName')->willReturn(self::STUB_WEBSITE_1_NAME);
        $groupMock->expects($this->any())->method('getStores')->willReturn($storeViews);
        $groupMock->expects($this->any())->method('getName')->willReturn(self::STUB_STORE_1_NAME);
        $websiteMock->expects($this->atLeastOnce())->method('getGroups')->willReturn([$groupMock]);
        $this->storeManagerMock->expects($this->any())->method('getWebsites')->willReturn(
            [
                $websiteMock
            ]
        );

        $result = $this->block->getStoreSelectorOptions();

        $this->assertSame($expectedResult, json_decode($result, true));
    }

    /**
     * Providing store views for the website
     *
     * @return array
     */
    public function storeViewsDataProvider(): array
    {
        $enabledStoreView = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->setMethods([])
            ->getMock();
        $disabledStoreView = clone $enabledStoreView;

        // Store View 1
        $enabledStoreView->expects($this->any())->method('isActive')->willReturn(true);
        $enabledStoreView->expects($this->any())->method('getName')
            ->willReturn(self::STUB_STORE_VIEW_EN_NAME);
        $enabledStoreView->expects($this->any())->method('getCode')
            ->willReturn(self::STUB_STORE_VIEW_EN_CODE);
        $enabledStoreView->expects($this->any())->method('getBaseUrl')
            ->willReturn(self::STUB_STORE_VIEW_EN_URL);

        // Store View 2
        $disabledStoreView->expects($this->any())->method('isActive')->willReturn(false);
        $disabledStoreView->expects($this->any())->method('getName')
            ->willReturn(self::STUB_STORE_VIEW_FR_NAME);
        $disabledStoreView->expects($this->any())->method('getCode')
            ->willReturn(self::STUB_STORE_VIEW_FR_CODE);
        $disabledStoreView->expects($this->any())->method('getBaseUrl')
            ->willReturn(self::STUB_STORE_VIEW_FR_URL);

        return [
            '1 website with an active store view' => [
                [
                    $enabledStoreView
                ],
                [
                    [
                        'label' => self::STUB_WEBSITE_1_NAME,
                        'value' => [
                            [
                                'label' => self::STUB_STORE_1_NAME,
                                'value' => [
                                    [
                                        'label' => self::STUB_STORE_VIEW_EN_NAME,
                                        'baseUrl' => self::STUB_STORE_VIEW_EN_URL,
                                        'value' => self::STUB_STORE_VIEW_EN_CODE,
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            '1 website with 2 store views, where just 1 store view is active' => [
                [
                    $enabledStoreView,
                    $disabledStoreView
                ],
                [
                    [
                        'label' => self::STUB_WEBSITE_1_NAME,
                        'value' => [
                            [
                                'label' => self::STUB_STORE_1_NAME,
                                'value' => [
                                    [
                                        'label' => self::STUB_STORE_VIEW_EN_NAME,
                                        'baseUrl' => self::STUB_STORE_VIEW_EN_URL,
                                        'value' => self::STUB_STORE_VIEW_EN_CODE,
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            '1 website with one disabled store view' => [
                [
                    $disabledStoreView
                ],
                []
            ]
        ];
    }
}
