<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Cms\Model\Page;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\ScopeResolver;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node as NodeResourceModel;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use Magento\VersionsCms\Observer\Backend\CmsPageSaveAfterObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CmsPageSaveAfterObserverTest extends TestCase
{
    /**
     * @var Hierarchy|MockObject
     */
    protected $cmsHierarchyMock;

    /**
     * @var Node|MockObject
     */
    protected $hierarchyNodeMock;

    /**
     * @var NodeResourceModel|MockObject
     */
    protected $hierarchyNodeResourceMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var Page|MockObject
     */
    protected $pageMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var CmsPageSaveAfterObserver
     */
    protected $observer;

    /**
     * @var Collection
     */
    protected $nodeCollectionMock;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactoryMock;

    /**
     * @var ScopeResolver|MockObject
     */
    protected $scopeResolverMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->cmsHierarchyMock = $this->createMock(Hierarchy::class);
        $this->hierarchyNodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getScopeId',
                'addData',
                'setParentNodeId',
                'unsetData',
                'setLevel',
                'setSortOrder',
                'setRequestUrl',
                'setXpath',
                'updateRewriteUrls',
                'setScopeId',
                'save',
                'getScope',
            ])
            ->getMock();

        $this->hierarchyNodeResourceMock = $this->createMock(
            NodeResourceModel::class
        );
        $this->eventObserverMock = $this->createMock(Observer::class);
        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'dataHasChangedFor',
                'getAppendToNodes',
                'getNodesSortOrder',
                'getId',
                'getStoreId',
                'getIdentifier',
            ])
            ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->nodeCollectionMock = $this->getMockBuilder(
            Collection::class
        )->disableOriginalConstructor()
            ->setMethods(
                [
                    'joinPageExistsNodeInfo',
                    'getData',
                    'applyPageExistsOrNodeIdFilter',
                    'getItems',
                    'getSize',
                    'addFieldToFilter'
                ]
            )
            ->getMock();
        $this->scopeResolverMock = $this->getMockBuilder(ScopeResolver::class)
            ->onlyMethods(['isBelongsToScope'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer = $this->objectManagerHelper->getObject(
            CmsPageSaveAfterObserver::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'hierarchyNode' => $this->hierarchyNodeMock,
                'hierarchyNodeResource' => $this->hierarchyNodeResourceMock,
                'nodeCollectionFactory' => $this->collectionFactoryMock,
                'scopeResolver' => $this->scopeResolverMock,
            ]
        );
    }

    /**
     * Test for cms page save after observer
     *
     * @dataProvider testCmsPageSaveAfterDataProvider
     * @param array $appendToNodes
     * @param int|null $getScopeId
     * @param int|null $getSizeFirstCall
     * @param int|null $getSizeSecondCall
     * @param int $removePagesCount
     * @param bool $isBelongsToScope
     * @param string $parentNodeScope
     * @return void
     */
    public function testCmsPageSaveAfter(
        array $appendToNodes,
        ?int $getScopeId,
        int $getSizeFirstCall,
        int $getSizeSecondCall,
        int $removePagesCount,
        bool $isBelongsToScope,
        string $parentNodeScope,
        int $getSizeThirdCall = 0
    ) {
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

        $this->cmsHierarchyMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->pageMock->expects($this->once())
            ->method('dataHasChangedFor')
            ->with('identifier')
            ->willReturn(true);
        $this->hierarchyNodeMock->expects($this->once())
            ->method('updateRewriteUrls')
            ->with($this->pageMock)
            ->willReturnSelf();
        $this->pageMock->expects($this->once())
            ->method('getAppendToNodes')
            ->willReturn($appendToNodes);
        $this->pageMock->method('getNodesSortOrder')
            ->willReturn([1 => 1]);
        $this->hierarchyNodeResourceMock->expects($this->once())
            ->method('updateSortOrder')
            ->with(1, 1)
            ->willReturnSelf();

        $this->collectionFactoryMock->method('create')
            ->willReturn($this->nodeCollectionMock);

        $this->nodeCollectionMock->method('joinPageExistsNodeInfo')->willReturnSelf();
        $this->nodeCollectionMock->method('getSize')->willReturnMap(
            [
                [$getSizeFirstCall],
                [$getSizeSecondCall],
                [$getSizeThirdCall]
            ]
        );
        $this->nodeCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->nodeCollectionMock->method('applyPageExistsOrNodeIdFilter')
            ->willReturn([$this->hierarchyNodeMock]);

        $this->hierarchyNodeMock->method('getId')->willReturn(1);
        $this->hierarchyNodeMock->method('getScopeId')->willReturn($getScopeId);
        $this->hierarchyNodeMock->method('getScope')->willReturn($parentNodeScope);
        $this->hierarchyNodeMock->method('addData')->willReturnSelf();
        $this->hierarchyNodeMock->method('setParentNodeId')->willReturnSelf();
        $this->hierarchyNodeMock->method('unsetData')->willReturnSelf();
        $this->hierarchyNodeMock->method('setLevel')->willReturnSelf();
        $this->hierarchyNodeMock->method('setSortOrder')->willReturnSelf();
        $this->hierarchyNodeMock->method('setRequestUrl')->willReturnSelf();
        $this->hierarchyNodeMock->method('setXpath')->willReturnSelf();
        $this->hierarchyNodeMock->method('save')->willReturnSelf();

        $this->pageMock->method('getStoreId')->willReturn(1);
        $this->pageMock->method('getId')->willReturn(1);
        $this->pageMock->method('getIdentifier')->willReturn('test_page');

        $this->hierarchyNodeResourceMock->expects($this->exactly($removePagesCount))
            ->method('removePageFromNodes')
            ->willReturnSelf();

        $this->scopeResolverMock->method('isBelongsToScope')->willReturn($isBelongsToScope);
        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @return array
     */
    public function testCmsPageSaveAfterDataProvider()
    {
        return [
            'Delete node after uncheck checkbox' => [
                'appendToNodes' => [],
                'getScopeId' => 1,
                'getSize' => 1,
                'getSizeSecondTime' => 1,
                'removePagesCallCount' => 1,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'store'
            ],
            'Nodes without changing' => [
                'appendToNodes' => [1 => 1], // key - node id, value - page scope id
                'getScopeId' => 1, // node scope id
                'getSize' => 0,
                'getSizeSecondTime' => 1,
                'removePagesCallCount' => 0,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'store'
            ],
            'Create new node' => [
                'appendToNodes' => [1 => 1],
                'getScopeId' => 1,
                'getSize' => 0,
                'getSizeSecondTime' => 0,
                'removePagesCallCount' => 0,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'store'
            ],
            'Delete node after changing Page in Websites' => [
                'appendToNodes' => [1 => 1],
                'getScopeId' => 2,
                'getSize' => 0,
                'getSizeSecondTime' => 1,
                'removePagesCallCount' => 1,
                'isBelongsToScope' => false,
                'parentNodeScope' => 'store'
            ],
            'Create node when node in "All Store View" and page scope id = 1' => [
                'appendToNodes' => [1 => 1],
                'getScopeId' => 0,
                'getSize' => 0,
                'getSizeSecondTime' => 1,
                'removePagesCallCount' => 0,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'default'
            ],
            'Create node when page node not exist' => [
                'appendToNodes' => [1 => 1],
                'getScopeId' => 1,
                'getSize' => 0,
                'getSizeSecondTime' => 0,
                'removePagesCallCount' => 0,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'store',
                'getSizeThirdCall' => 0
            ],
            'Create node when page node already exist' => [
                'appendToNodes' => [],
                'getScopeId' => 1,
                'getSize' => 1,
                'getSizeSecondTime' => 1,
                'removePagesCallCount' => 1,
                'isBelongsToScope' => true,
                'parentNodeScope' => 'store',
                'getSizeThirdCall' => 3
            ],
        ];
    }

    /**
     * @return void
     */
    public function testCmsPageSaveAfterWithCmsHierarchyDisabled()
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

        $this->cmsHierarchyMock->method('isEnabled')
            ->willReturn(false);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }
}
