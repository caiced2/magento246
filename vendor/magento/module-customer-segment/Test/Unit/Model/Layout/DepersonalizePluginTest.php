<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Layout;

use Magento\Customer\Model\Context;
use Magento\Customer\Model\Session;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\Layout\DepersonalizePlugin;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\CustomerSegment\Model\Layout\DepersonalizePlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DepersonalizePluginTest extends TestCase
{
    /**
     * @var DepersonalizePlugin
     */
    private $plugin;

    /**
     * @var LayoutInterface|MockObject
     */
    private $layoutMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var HttpContext|MockObject
     */
    private $httpContextMock;

    /**
     * @var Manager|MockObject
     */
    private $moduleManagerMock;

    /**
     * @var Config|MockObject
     */
    private $cacheConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->httpContextMock = $this->createMock(HttpContext::class);
        $this->layoutMock = $this->createMock(Layout::class);
        $this->moduleManagerMock = $this->createMock(Manager::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->cacheConfig = $this->createMock(Config::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->plugin = (new ObjectManagerHelper($this))->getObject(
            DepersonalizePlugin::class,
            [
                'customerSession' => $this->customerSessionMock,
                'request' => $this->requestMock,
                'moduleManager' => $this->moduleManagerMock,
                'httpContext' => $this->httpContextMock,
                'cacheConfig' => $this->cacheConfig,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * Test afterGenerateElements method when Magento_PageCache is enabled and the layout is cacheable.
     *
     * @dataProvider afterGenerateElementsDataProvider
     * @param bool $isCustomerLoggedIn
     * @return void
     */
    public function testAfterGenerateElements(bool $isCustomerLoggedIn): void
    {
        $websiteId = 1;
        $customerSegmentIds = [1 => [1, 2, 3]];
        $expectedCustomerSegmentIds = [1, 2, 3];
        $defaultCustomerSegmentIds = [];

        if (!$isCustomerLoggedIn) {
            $defaultCustomerSegmentIds = $expectedCustomerSegmentIds;
        }

        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with('Magento_PageCache')
            ->willReturn(true);
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->willReturn(false);
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($customerSegmentIds);
        $this->customerSessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($customerSegmentIds);
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with(null)
            ->willReturn($websiteMock);

        $this->httpContextMock->expects($this->once())
            ->method('getValue')
            ->with(Context::CONTEXT_AUTH)
            ->willReturn($isCustomerLoggedIn);
        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(
                Data::CONTEXT_SEGMENT,
                $expectedCustomerSegmentIds,
                $defaultCustomerSegmentIds
            );

        $this->plugin->beforeGenerateXml($this->layoutMock);
        $this->assertEmpty($this->plugin->afterGenerateElements($this->layoutMock));
    }

    /**
     * @return array
     */
    public function afterGenerateElementsDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @return void
     */
    public function testBeforeGenerateXmlWithNoWebsite(): void
    {
        $websiteId = 2;
        $customerSegmentIds = [1 => [1, 2, 3]];
        $expectedCustomerSegmentIds = [];
        $defaultCustomerSegmentIds = [];
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with('Magento_PageCache')
            ->willReturn(true);
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->willReturn(false);
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($customerSegmentIds);
        $this->customerSessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($customerSegmentIds);
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with(null)
            ->willReturn($websiteMock);
        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(
                Data::CONTEXT_SEGMENT,
                $expectedCustomerSegmentIds,
                $defaultCustomerSegmentIds
            );
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $this->assertEmpty($this->plugin->afterGenerateElements($this->layoutMock));
    }

    /**
     * @return void
     */
    public function testUsualBehaviorIsAjax(): void
    {
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with('Magento_PageCache')
            ->willReturn(true);
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->willReturn(true);
        $this->layoutMock->expects($this->never())
            ->method('isCacheable');
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $this->assertEmpty($this->plugin->afterGenerateElements($this->layoutMock));
    }

    /**
     * @return void
     */
    public function testUsualBehaviorNonCacheable(): void
    {
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with('Magento_PageCache')
            ->willReturn(true);
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->willReturn(false);
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->willReturn(false);
        $this->customerSessionMock->expects($this->never())
            ->method('setCustomerSegmentIds');
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $this->assertEmpty($this->plugin->afterGenerateElements($this->layoutMock));
    }
}
