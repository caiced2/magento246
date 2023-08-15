<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Observer\Backend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\VersionsCms\Block\Widget\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Observer\Backend\CleanStoreFootprints;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\Widget\Model\Widget\Instance;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CleanStoreFootprintsTest extends TestCase
{
    /**
     * @var NodeFactory|MockObject
     */
    protected $hierarchyNodeFactoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $widgetCollectionFactoryMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var CleanStoreFootprints
     */
    protected $unit;

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
        $this->widgetCollectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->unit = $this->objectManagerHelper->getObject(
            CleanStoreFootprints::class,
            [
                'hierarchyNodeFactory' => $this->hierarchyNodeFactoryMock,
                'widgetCollectionFactory' => $this->widgetCollectionFactoryMock,
            ]
        );
    }

    public function testCleanStoreFootprints()
    {
        $storeId = 2;

        $this->hierarchyNodeDeleteByScope();
        /** @var Instance|MockObject $widgetInstanceMock */
        $widgetInstanceMock = $this->getMockBuilder(Instance::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds', 'setStoreIds', 'getWidgetParameters', 'setWidgetParameters', 'save'])
            ->getMock();
        $widgetInstanceMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([0 => 1, 1 => $storeId, 2 => 3]);
        $widgetInstanceMock->expects($this->once())
            ->method('setStoreIds')
            ->with([0 => 1, 2 => 3]);
        $widgetInstanceMock->expects($this->once())
            ->method('getWidgetParameters')
            ->willReturn([
                'anchor_text_' . $storeId => 'test',
                'title_' . $storeId => 'test',
                'node_id_' . $storeId => 'test',
                'template_' . $storeId => 'test',
                'someParameter'  => 'test'
            ]);
        $widgetInstanceMock->expects($this->once())
            ->method('setWidgetParameters')
            ->with(['someParameter'  => 'test']);
        $widgetInstanceMock->expects($this->once())
            ->method('save');

        /** @var Collection|MockObject $widgetsCollectionMock */
        $widgetsCollectionMock = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $widgetsCollectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with([$storeId, false])
            ->willReturnSelf();
        $widgetsCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('instance_type', Node::class)
            ->willReturnSelf();
        $widgetsCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$widgetInstanceMock]));
        $this->widgetCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($widgetsCollectionMock);

        $this->unit->clean($storeId);
    }

    /**
     * @return void
     */
    protected function hierarchyNodeDeleteByScope()
    {
        /** @var \Magento\VersionsCms\Model\Hierarchy\Node|MockObject $hierarchyNode */
        $hierarchyNode = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $hierarchyNode->expects($this->any())->method('deleteByScope');
        $this->hierarchyNodeFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($hierarchyNode);
    }
}
