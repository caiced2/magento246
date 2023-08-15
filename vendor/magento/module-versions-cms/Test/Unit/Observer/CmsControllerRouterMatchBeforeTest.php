<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Observer\AddCmsToTopmenuItems;
use Magento\VersionsCms\Observer\CmsControllerRouterMatchBefore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CmsControllerRouterMatchBeforeTest extends TestCase
{
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
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    /**
     * @var Hierarchy|MockObject
     */
    private $cmsHierarchyMock;

    /**
     * @var AddCmsToTopmenuItems
     */
    private $observer;

    protected function setUp(): void
    {
        $this->cmsHierarchyMock = $this->getMockBuilder(Hierarchy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            CmsControllerRouterMatchBefore::class,
            [
                'cmsHierarchy' => $this->cmsHierarchyMock,
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    public function testExecute()
    {
        $identifier = 'identifier';
        $storeId = 1;
        $nodeId = 1;
        $pageId = 1;

        $condition = new DataObject(['identifier' => $identifier, 'continue' => true]);

        $this->cmsHierarchyMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCondition',
            ])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCondition')
            ->willReturn($condition);

        $eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $hierarchyNode1 = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHeritage',
                'loadByRequestUrl',
                'checkIdentifier',
                'getId',
                'getPageId',
                'getPageIsActive',
                'getPageIdentifier',
            ])
            ->getMock();
        $hierarchyNode1->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('loadByRequestUrl')
            ->with($identifier)
            ->willReturnSelf();
        $hierarchyNode1->expects($this->once())
            ->method('checkIdentifier')
            ->with($identifier, $this->storeMock)
            ->willReturn(false);
        $hierarchyNode1->expects($this->once())
            ->method('getId')
            ->willReturn($nodeId);
        $hierarchyNode1->expects($this->once())
            ->method('getPageId')
            ->willReturn($pageId);
        $hierarchyNode1->expects($this->once())
            ->method('getPageIsActive')
            ->willReturn(true);
        $hierarchyNode1->expects($this->once())
            ->method('getPageIdentifier')
            ->willReturn($identifier);

        $hierarchyNode1Data = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($hierarchyNode1Data)
            ->willReturn($hierarchyNode1);

        $this->observer->execute($eventObserverMock);
    }
}
