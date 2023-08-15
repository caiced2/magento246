<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Tree;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Observer\AddCmsToTopmenuItems;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddCmsToTopmenuItemsTest extends TestCase
{
    /**
     * @var AddCmsToTopmenuItems
     */
    private $observer;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var NodeFactory|MockObject
     */
    private $hierarchyNodeFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $objectManagerHelper = new ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            AddCmsToTopmenuItems::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'currentNodeResolver' => $this->currentNodeResolverMock
            ]
        );
    }

    /**
     * @param MockObject|null $currentNode
     *
     * @return void
     * @dataProvider getExecuteDataProvider
     */
    public function testExecute(?MockObject $currentNode): void
    {
        $storeId = 1;
        $nodeId = 1;
        $nodeLabel = 'label';
        $nodeUrl = 'url';
        $parentNodeId = 1;
        $pageId = 1;

        $hierarchyNode1Data = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $hierarchyNode1 = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hierarchyNode1->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('getNodesData')
            ->willReturn([
                [
                    'node_id' => $nodeId,
                    'parent_node_id' => $parentNodeId,
                ]
            ]);

        $hierarchyNode2 = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'load',
                    'getPageId',
                    'getLabel',
                    'getUrl'
                ]
            )
            ->addMethods(
                [
                    'getParentNodeId',
                    'getTopMenuExcluded',
                    'getPageIsActive'
                ]
            )
            ->getMock();
        $hierarchyNode2->expects($this->once())
            ->method('load')
            ->with($nodeId)
            ->willReturnSelf();
        $hierarchyNode2->expects($this->any())
            ->method('getParentNodeId')
            ->willReturn($parentNodeId);
        $hierarchyNode2->expects($this->once())
            ->method('getTopMenuExcluded')
            ->willReturn(false);
        $hierarchyNode2->expects($this->once())
            ->method('getPageId')
            ->willReturn($pageId);
        $hierarchyNode2->expects($this->once())
            ->method('getPageIsActive')
            ->willReturn(true);
        $hierarchyNode2->expects($this->once())
            ->method('getLabel')
            ->willReturn($nodeLabel);
        $hierarchyNode2->expects($this->once())
            ->method('getUrl')
            ->willReturn($nodeUrl);

        $this->hierarchyNodeFactoryMock
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $this->returnValueMap(
                    [
                        [$hierarchyNode1Data, $hierarchyNode1],
                        [[], $hierarchyNode2]
                    ]
                ),
                $hierarchyNode2
            );

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($currentNode);

        $eventObserverMock = $this->getEventObserverMock();
        $this->observer->execute($eventObserverMock);
    }

    /**
     * Data Provider for testExecute() method.
     *
     * @return array
     */
    public function getExecuteDataProvider(): array
    {
        $currentNodeMock = $this->getMockBuilder(HierarchyNodeInterface::class)
            ->getMockForAbstractClass();
        $currentNodeMock->expects($this->once())
            ->method('getXpath')
            ->willReturn('1/2');

        return [
            [$currentNodeMock],
            [null]
        ];
    }

    /**
     * Create Event Observer mock object
     *
     * Helper method, that provides unified logic of creation of Event Observer mock object,
     * required to implement test iterations.
     *
     * Used to avoid creation test methods with too many rows of code.
     *
     * @return Observer|MockObject
     */
    private function getEventObserverMock(): Observer
    {
        $topMenuRootNodeId = 1;

        $treeMock = $this->getMockBuilder(Tree::class)
            ->disableOriginalConstructor()
            ->getMock();

        $topMenuRootNodeMock = $this->getMockBuilder(\Magento\Framework\Data\Tree\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $topMenuRootNodeMock->expects($this->once())
            ->method('getTree')
            ->willReturn($treeMock);
        $topMenuRootNodeMock->expects($this->once())
            ->method('getId')
            ->willReturn($topMenuRootNodeId);

        $eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getRequest', 'getMenu'])
            ->getMock();
        $eventObserverMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $eventObserverMock->expects($this->once())
            ->method('getMenu')
            ->willReturn($topMenuRootNodeMock);

        return $eventObserverMock;
    }
}
