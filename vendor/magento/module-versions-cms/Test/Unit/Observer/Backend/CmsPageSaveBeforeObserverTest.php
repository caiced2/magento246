<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use InvalidArgumentException;
use Magento\Cms\Model\Page;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Observer\Backend\CmsPageSaveBeforeObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CmsPageSaveBeforeObserverTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    protected $jsonHelperMock;

    /**
     * @var Page|MockObject
     */
    protected $pageMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var CmsPageSaveBeforeObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->jsonHelperMock = $this->createMock(Data::class);
        $this->eventObserverMock = $this->createMock(Observer::class);
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'setWebsiteRoot', 'setNodesSortOrder', 'setAppendToNodes', 'getNodesData'])
            ->getMock();

        $this->observer = $this->objectManagerHelper->getObject(
            CmsPageSaveBeforeObserver::class,
            [
                'jsonHelper' => $this->jsonHelperMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBeforeNewPageAndEmptyNodesData()
    {
        $this->initEventMock();

        $this->pageMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->pageMock->expects($this->once())
            ->method('setWebsiteRoot')
            ->with(true);
        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with([]);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with(null);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @param array $nodesData
     * @param int|null $pageId
     * @param array $nodesSortOrder
     * @param array $appendToNodes
     * @param int $callsCount
     *
     * @dataProvider setWebsiteRootDataProvider
     * @return void
     */
    public function testCmsPageSaveBeforeOldPageWithNodesData(
        ?int $pageId,
        array $nodesData,
        array $nodesSortOrder,
        array $appendToNodes,
        int $callsCount
    ) {
        $nodesJsonData = 'Some JSON data';

        $this->initEventMock();

        $this->pageMock
            ->method('getId')
            ->willReturn($pageId);
        $this->pageMock->expects($this->once())
            ->method('getNodesData')
            ->willReturn($nodesJsonData);

        $this->jsonHelperMock->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesJsonData)
            ->willReturn($nodesData);

        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with($nodesSortOrder);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with($appendToNodes);

        $this->pageMock->expects($this->exactly($callsCount))
            ->method('setWebsiteRoot')
            ->with(true);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * Return number of calls to setWebsiteRoot method with different variations of page_id and nodes_data
     *
     * @return array
     */
    public function setWebsiteRootDataProvider()
    {
        return [
            [
                'pageId' => null,
                'nodes_data' => [
                    [
                        'page_exists' => true,
                        'node_id' => '0_1',
                        'parent_node_id' => '0',
                        'sort_order' => 10
                    ],
                    [
                        'page_exists' => true,
                        'node_id' => '1',
                        'parent_node_id' => '1',
                        'sort_order' => 20
                    ]
                ],
                'set_nodes_sort_order' => [1 => 20],
                'append_to_nodes' => [
                    '0_1' => '0',
                    '1' => '0'
                ],
                'function_calls_count' => 0
            ],
            [
                'pageId' => 1,
                'nodes_data' => [],
                'set_nodes_sort_order' => [],
                'append_to_nodes' => [],
                'function_calls_count' => 0
            ],
            [
                'pageId' => null,
                'nodes_data' => [],
                'set_nodes_sort_order' => [],
                'append_to_nodes' => [],
                'function_calls_count' => 1
            ],
        ];
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBeforeWithException()
    {
        $nodesJsonData = 'Some JSON data';
        $this->initEventMock();

        $this->pageMock->expects($this->once())
            ->method('getNodesData')
            ->willReturn($nodesJsonData);

        $this->jsonHelperMock->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesJsonData)
            ->willThrowException(new InvalidArgumentException());

        $this->pageMock->expects($this->once())
            ->method('setNodesSortOrder')
            ->with([]);
        $this->pageMock->expects($this->once())
            ->method('setAppendToNodes')
            ->with([]);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return void
     */
    protected function initEventMock()
    {
        /** @var Event|MockObject $eventMock */
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getObject')
            ->willReturn($this->pageMock);
        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);
    }
}
