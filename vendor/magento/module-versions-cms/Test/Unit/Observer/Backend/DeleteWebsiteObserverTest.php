<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Website;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Observer\Backend\CleanStoreFootprints;
use Magento\VersionsCms\Observer\Backend\DeleteWebsiteObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteWebsiteObserverTest extends TestCase
{
    /**
     * @var NodeFactory|MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var CleanStoreFootprints|MockObject
     */
    protected $cleanStoreFootprintsMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var DeleteWebsiteObserver
     */
    protected $observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->hierarchyNodeFactoryMock = $this->createPartialMock(
            NodeFactory::class,
            ['create']
        );
        $this->cleanStoreFootprintsMock = $this->createMock(
            CleanStoreFootprints::class
        );
        $this->eventObserverMock = $this->createMock(Observer::class);

        $this->observer = $this->objectManagerHelper->getObject(
            DeleteWebsiteObserver::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'cleanStoreFootprints' => $this->cleanStoreFootprintsMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testDeleteWebsite()
    {
        $websiteId = 1;
        $storeId = 2;

        /** @var Website|MockObject $websiteMock */
        $websiteMock = $this->createPartialMock(Website::class, ['getId', 'getStoreIds']);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $websiteMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([$storeId]);

        $this->hierarchyNodeDeleteByScope($websiteId);

        /** @var Event|MockObject $eventMock */
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getWebsite'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->eventObserverMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $this->cleanStoreFootprintsMock->expects($this->once())->method('clean')->with($storeId);

        $this->assertSame(
            $this->observer,
            $this->observer->execute($this->eventObserverMock)
        );
    }

    /**
     * @param int $id
     * @return void
     */
    protected function hierarchyNodeDeleteByScope($id)
    {
        /** @var Node|MockObject $hierarchyNode */
        $hierarchyNode = $this->createMock(Node::class);
        $hierarchyNode->expects($this->any())
            ->method('deleteByScope')
            ->willReturnMap([
                [Node::NODE_SCOPE_STORE, $id],
                [Node::NODE_SCOPE_WEBSITE, $id]
            ]);
        $this->hierarchyNodeFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($hierarchyNode);
    }
}
