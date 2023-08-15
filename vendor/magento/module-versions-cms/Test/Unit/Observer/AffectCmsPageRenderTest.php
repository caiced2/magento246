<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer;

use Magento\Cms\Model\Page;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node as NodeMock;
use Magento\VersionsCms\Observer\AffectCmsPageRender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AffectCmsPageRenderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\VersionsCms\Helper\Hierarchy|MockObject
     */
    protected $cmsHierarchyMock;

    /**
     * @var ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserver;

    /**
     * @var ProcessorInterface|MockObject
     */
    protected $updateMock;

    /**
     * @var AffectCmsPageRender
     */
    protected $observer;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cmsHierarchyMock = $this->createMock(Hierarchy::class);
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getPage',
                'getRequest',
            ])
            ->getMock();

        $this->updateMock = $this->getMockForAbstractClass(ProcessorInterface::class);

        /** @var LayoutInterface|MockObject $layoutMock */
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $layoutMock->expects($this->any())
            ->method('getUpdate')
            ->willReturn($this->updateMock);
        $this->viewMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->observer = $this->objectManagerHelper->getObject(
            AffectCmsPageRender::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'view' => $this->viewMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    /**
     * @param NodeMock|null $node
     * @param bool $hierarchyEnabled
     * @return void
     * @dataProvider invokeWhenHierarchyDisabledOrNodeAbsentDataProvider
     */
    public function testInvokeWhenHierarchyDisabledOrNodeAbsent($node, $hierarchyEnabled)
    {
        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($node);

        $this->cmsHierarchyMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn($hierarchyEnabled);

        $this->updateMock->expects($this->never())
            ->method('getHandles');
        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->eventObserver->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return array
     */
    public function invokeWhenHierarchyDisabledOrNodeAbsentDataProvider()
    {
        return [
            ['node' => null, 'hierarchyEnabled' => true],
            ['node' => null, 'hierarchyEnabled' => false],
            ['node' => $this->getNodeMock(), 'hierarchyEnabled' => false]
        ];
    }

    /**
     * @return void
     */
    public function testInvokeWhenMenuLayoutEmpty()
    {
        $this->configureMockObjects(null, '2columns-right', []);

        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return void
     */
    public function testInvokeWhenAllowedNonIntersectLoadedHandles()
    {
        $loadedHandles = ['default', 'cms_page'];
        $menuLayout = [
            'pageLayoutHandles' => ['2columns-left', '3columns'],
            'handle' => 'menu_left_column'
        ];

        $this->configureMockObjects($menuLayout, '2columns-right', $loadedHandles);

        $this->updateMock->expects($this->never())
            ->method('addHandle');

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * @return void
     */
    public function testInvoke()
    {
        $loadedHandles = ['default', 'cms_page'];
        $menuLayout = [
            'pageLayoutHandles' => ['2columns-left', '3columns'],
            'handle' => 'menu_left_column'
        ];

        $this->configureMockObjects($menuLayout, '2columns-left', $loadedHandles);

        $this->updateMock->expects($this->once())
            ->method('addHandle')
            ->with($menuLayout['handle']);

        $this->assertSame($this->observer, $this->observer->execute($this->eventObserver));
    }

    /**
     * Configure mock objects
     *
     * Helper method, that creates mock objects and applies configuration to mock objects,
     * required for test iterations.
     *
     * @param array|null $menuLayout
     * @param string $pageLayout
     * @param array $loadedHandles
     * @return void
     */
    protected function configureMockObjects($menuLayout, $pageLayout, $loadedHandles)
    {
        $nodeMock = $this->getNodeMock();
        $nodeMock->expects($this->once())
            ->method('getMenuLayout')
            ->willReturn($menuLayout);

        /** @var Page|MockObject $pageMock */
        $pageMock = $this->createMock(Page::class);
        $pageMock->expects($this->once())
            ->method('getPageLayout')
            ->willReturn($pageLayout);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);
        $this->cmsHierarchyMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
        $this->updateMock->expects($this->once())
            ->method('getHandles')
            ->willReturn($loadedHandles);
        $this->eventObserver->expects($this->once())
            ->method('getPage')
            ->willReturn($pageMock);
        $this->eventObserver->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
    }

    /**
     * Create Hierarchy Node mock object
     *
     * Helper method, that provides unified logic of creation of Hierarchy Node mock object.
     *
     * @return NodeMock|MockObject
     */
    protected function getNodeMock()
    {
        return $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
    }
}
