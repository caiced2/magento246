<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Controller\Index;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\GoogleTagManager\Controller\Index\Get;
use Magento\GoogleTagManager\Helper\CookieData;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\GoogleTagManager\Test\Unit\Controller\Index\Get
 */
class GetTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var ResultInterface|MockObject
     */
    private $resultMock;

    /**
     * @var Json|MockObject
     */
    private $jsonResult;

    /**
     * @var SessionManagerInterface|MockObject
     */
    private $sessionManagerMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManagerMock;

    /**
     * @var CookieData|MockObject
     */
    private $helperMock;

    /**
     * @var TagManagerConfig|MockObject
     */
    private $configMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactoryMock;

    /**
     * @var Get
     */
    private $getAction;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['isAjax', 'isPost'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultMock = $this->getMockForAbstractClass(ResultInterface::class);

        $this->jsonResult = $this->createMock(Json::class);

        $resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $resultJsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonResult);

        $this->sessionManagerMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAddToCartAdvanced', 'unsAddToCartAdvanced'])
            ->getMockForAbstractClass();

        $this->cookieManagerMock = $this->getMockForAbstractClass(CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->createMock(
            CookieMetadataFactory::class
        );

        $this->helperMock = $this->createMock(CookieData::class);
        $this->configMock = $this->createMock(TagManagerConfig::class);

        $this->getAction = new Get(
            $this->requestMock,
            $this->resultFactory,
            $this->helperMock,
            $this->configMock,
            $this->sessionManagerMock,
            $this->cookieManagerMock,
            $resultJsonFactoryMock
        );
    }

    /**
     * @param bool $gtmEnabled
     * @param bool $gaEnabled
     * @param bool $isAjax
     * @param bool $isPost
     * @param string $cookieContent
     * @return void
     * @throws NotFoundException
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool   $gtmEnabled,
        bool   $gaEnabled,
        bool   $isAjax,
        bool   $isPost,
        string $cookieContent
    ): void {
        $this->helperMock->expects($this->any())
            ->method('isTagManagerAvailable')
            ->willReturn($gtmEnabled);
        $this->helperMock->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn($gaEnabled);

        $this->configMock->expects($this->any())
            ->method('isTagManagerAvailable')
            ->willReturn($gtmEnabled);
        $this->configMock->expects($this->any())
            ->method('isGoogleAnalyticsAvailable')
            ->willReturn($gaEnabled);

        $this->requestMock->expects($this->any())
            ->method('isAjax')
            ->willReturn($isAjax);
        $this->requestMock->expects($this->any())
            ->method('isPost')
            ->willReturn($isPost);

        if ($gtmEnabled && $gaEnabled && $isAjax && $isPost && $cookieContent) {
            $this->cookieManagerMock->expects($this->once())
                ->method('getCookie')
                ->with(CookieData::GOOGLE_ANALYTICS_COOKIE_ADVANCED_NAME)
                ->willReturn(true);

            $this->sessionManagerMock->expects($this->once())
                ->method('getAddToCartAdvanced')
                ->willReturn($cookieContent);

            $this->sessionManagerMock->expects($this->once())
                ->method('unsAddToCartAdvanced')
                ->willReturnSelf();
            $this->jsonResult->expects($this->once())
                ->method('setData')
                ->with($cookieContent)
                ->willReturnSelf();
            $this->assertEquals($this->jsonResult, $this->getAction->execute());
        } else {
            $this->resultMock->expects($this->any())
                ->method('setHttpResponseCode')
                ->with(404)
                ->willReturnSelf();
            $this->resultFactory->expects($this->any())
                ->method('create')
                ->with(ResultFactory::TYPE_RAW)
                ->willReturn($this->resultMock);
            $this->assertEquals($this->resultMock, $this->getAction->execute());
        }
    }

    /**
     * Data Provider for execute test
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'disabled_gtm_ga' => [false, false, false, false, ''],
            'enabled_gtm_ga_not_ajax_post' => [true, true, false, false, ''],
            'enabled_gtm_ga_ajax_post_no_cookie' => [true, true, true, true, ''],
            'enabled_gtm_ga_ajax_post_cookie' => [true, true, true, true,
                rawurldecode(json_encode(['products' => [
                    'id' => 'sku_1',
                    'name' => 'Simple Product 1',
                    'price' => 24.7, 'quantity' => 1
                ]
                ]))
            ]
        ];
    }
}
