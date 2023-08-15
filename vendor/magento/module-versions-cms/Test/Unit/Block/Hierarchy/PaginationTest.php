<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Block\Hierarchy;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\VersionsCms\Block\Hierarchy\Pagination;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var Node|MockObject
     */
    protected $nodeMock;

    /**
     * @var NodeFactory|MockObject
     */
    protected $nodeFactoryMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->nodeMock = $this->getMockBuilder(Node::class)
            ->addMethods(['getPageNumber'])
            ->onlyMethods(
                ['getId', 'getLabel', 'setCollectActivePagesOnly', 'getMetadataPagerParams', 'getParentNodeChildren']
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->nodeFactoryMock = $this->createMock(NodeFactory::class);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();
        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($this->nodeMock);

        $this->pagination = $this->objectManagerHelper->getObject(
            Pagination::class,
            [
                'nodeFactory' => $this->nodeFactoryMock,
                'context' => $this->contextMock,
                'currentNodeResolver' => $this->currentNodeResolverMock,
            ]
        );
    }

    /**
     * @param int $useNodeLabels
     * @param bool $expectedValue
     * @dataProvider getUseNodeLabelsDataProvider
     */
    public function testGetUseNodeLabels($useNodeLabels, $expectedValue)
    {
        $this->pagination->setData('use_node_labels', $useNodeLabels);
        $this->assertEquals($expectedValue, $this->pagination->getUseNodeLabels());
    }

    /**
     * @return array
     */
    public function getUseNodeLabelsDataProvider()
    {
        return [
            ['useNodeLabels' => 0, 'expectedValue' => false],
            ['useNodeLabels' => 1, 'expectedValue' => true]
        ];
    }

    /**
     * @param int $sequence
     * @param bool $expectedValue
     * @dataProvider canShowSequenceDataProvider
     */
    public function testCanShowSequence($sequence, $expectedValue)
    {
        $this->pagination->setData('sequence', $sequence);
        $this->assertEquals($expectedValue, $this->pagination->canShowSequence());
    }

    /**
     * @return array
     */
    public function canShowSequenceDataProvider()
    {
        return [
            ['sequence' => 1, 'expectedValue' => true],
            ['sequence' => 0, 'expectedValue' => false],
        ];
    }

    /**
     * @param int $outer
     * @param int $jump
     * @param bool $expectedValue
     * @dataProvider canShowOuterDataProvider
     */
    public function testCanShowOuter($outer, $jump, $expectedValue)
    {
        $this->pagination->setData('outer', $outer);
        $this->pagination->setData('jump', $jump);
        $this->assertEquals($expectedValue, $this->pagination->canShowOuter());
    }

    /**
     * @return array
     */
    public function canShowOuterDataProvider()
    {
        return [
            ['outer' => 1, 'jump' => 1, 'expectedValue' => true],
            ['outer' => 1, 'jump' => 0, 'expectedValue' => false],
            ['outer' => 0, 'jump' => 1, 'expectedValue' => false],
            ['outer' => 0, 'jump' => 0, 'expectedValue' => false],
        ];
    }

    /**
     * @param int|float $value
     * @param int $expectedValue
     * @dataProvider numbersDataProvider
     */
    public function testGetFrame($value, $expectedValue)
    {
        $this->pagination->setData('frame', $value);
        $this->assertEquals($expectedValue, $this->pagination->getFrame());
    }

    /**
     * @param int|float $value
     * @param int $expectedValue
     * @dataProvider numbersDataProvider
     */
    public function testGetJump($value, $expectedValue)
    {
        $this->pagination->setData('jump', $value);
        $this->assertEquals($expectedValue, $this->pagination->getJump());
    }

    /**
     * @return array
     */
    public function numbersDataProvider()
    {
        return [
            ['value' => 1, 'expectedValue' => 1],
            ['value' => 2.4, 'expectedValue' => 2],
            ['value' => -4.5, 'expectedValue' => 4],
        ];
    }

    public function testGetNodeLabel()
    {
        $pageNumber = 5;
        $label = 'Test label';
        $custom = 'Custom label';

        $this->nodeMock->expects($this->any())
            ->method('getLabel')
            ->willReturn($label);
        $this->nodeMock->expects($this->any())
            ->method('getPageNumber')
            ->willReturn($pageNumber);

        $this->assertEquals($pageNumber, $this->pagination->getNodeLabel($this->nodeMock));
        $this->assertEquals($custom, $this->pagination->getNodeLabel($this->nodeMock, $custom));

        $this->pagination->setData('use_node_labels', 1);
        $this->assertEquals($label, $this->pagination->getNodeLabel($this->nodeMock));
    }

    public function testCanShowFirst()
    {
        $this->pagination->setCanShowFirst(false);
        $this->assertFalse($this->pagination->canShowFirst());
        $this->pagination->setCanShowFirst(true);
        $this->assertTrue($this->pagination->canShowFirst());
    }

    public function testGetFirstNode()
    {
        $firstNone = 5;
        $this->pagination->setFirstNode($firstNone);
        $this->assertEquals($firstNone, $this->pagination->getFirstNode());
    }

    public function testCanShowLast()
    {
        $this->pagination->setCanShowLast(false);
        $this->assertFalse($this->pagination->CanShowLast());
        $this->pagination->setCanShowLast(true);
        $this->assertTrue($this->pagination->CanShowLast());
    }

    public function testGetLastNode()
    {
        $this->pagination->setLastNode($this->nodeMock);
        $this->assertSame($this->nodeMock, $this->pagination->getLastNode());
    }

    public function testCanShowPrevious()
    {
        $this->assertFalse($this->pagination->canShowPrevious());
        $this->pagination->setPreviousNode($this->nodeMock);
        $this->assertTrue($this->pagination->canShowPrevious());
    }

    public function testGetPreviousNode()
    {
        $this->pagination->setPreviousNode($this->nodeMock);
        $this->assertSame($this->nodeMock, $this->pagination->getPreviousNode());
    }

    public function testCanShowNext()
    {
        $this->assertFalse($this->pagination->canShowNext());
        $this->pagination->setNextNode($this->nodeMock);
        $this->assertTrue($this->pagination->canShowNext());
    }

    public function testGetNextNode()
    {
        $this->pagination->setNextNode($this->nodeMock);
        $this->assertSame($this->nodeMock, $this->pagination->getNextNode());
    }

    /**
     * @param int $jump
     * @param bool $showPreviousJump
     * @param bool $expectedValue
     * @dataProvider canShowPreviousJumpDataProvider
     */
    public function testCanShowPreviousJump($jump, $showPreviousJump, $expectedValue)
    {
        $this->pagination->setData('jump', $jump);
        $this->pagination->setCanShowPreviousJump($showPreviousJump);
        $this->assertEquals($expectedValue, $this->pagination->canShowPreviousJump());
    }

    /**
     * @return array
     */
    public function canShowPreviousJumpDataProvider()
    {
        return [
            ['jump' => 1, 'showPreviousJump' => true, 'expectedValue' => true],
            ['jump' => 0, 'showPreviousJump' => true, 'expectedValue' => false],
            ['jump' => 1, 'showPreviousJump' => false, 'expectedValue' => false],
            ['jump' => 0, 'showPreviousJump' => false, 'expectedValue' => false],
        ];
    }

    public function testGetPreviousJumpNode()
    {
        $this->pagination->setPreviousJump($this->nodeMock);
        $this->assertSame($this->nodeMock, $this->pagination->getPreviousJumpNode());
    }

    /**
     * @param int $jump
     * @param bool $showNextJump
     * @param bool $expectedValue
     * @dataProvider canShowNextJumpDataProvider
     */
    public function testCanShowNextJump($jump, $showNextJump, $expectedValue)
    {
        $this->pagination->setData('jump', $jump);
        $this->pagination->setCanShowNextJump($showNextJump);
        $this->assertEquals($expectedValue, $this->pagination->canShowNextJump());
    }

    /**
     * @return array
     */
    public function canShowNextJumpDataProvider()
    {
        return [
            ['jump' => 1, 'showNextJump' => true, 'expectedValue' => true],
            ['jump' => 0, 'showNextJump' => true, 'expectedValue' => false],
            ['jump' => 1, 'showNextJump' => false, 'expectedValue' => false],
            ['jump' => 0, 'showNextJump' => false, 'expectedValue' => false],
        ];
    }

    public function testGetNextJumpNode()
    {
        $this->pagination->setNextJump($this->nodeMock);
        $this->assertSame($this->nodeMock, $this->pagination->getNextJumpNode());
    }

    /**
     * @param int $outermost
     * @param bool $expectedValue
     * @dataProvider isShowOutermostDataProvider
     */
    public function testIsShowOutermost($outermost, $expectedValue)
    {
        $this->pagination->setData('outermost', $outermost);
        $this->assertEquals($expectedValue, $this->pagination->isShowOutermost());
    }

    /**
     * @return array
     */
    public function isShowOutermostDataProvider()
    {
        return [
            ['outermost' => 0, false],
            ['outermost' => 1, false],
            ['outermost' => 2, true],
            ['outermost' => 3, true],
        ];
    }

    public function testGetNodesEmpty()
    {
        $this->nodeMock->expects($this->once())
            ->method('setCollectActivePagesOnly')
            ->with(true)
            ->willReturnSelf();
        $this->nodeMock->expects($this->once())
            ->method('getParentNodeChildren')
            ->willReturn([]);

        $this->assertEmpty($this->pagination->getNodes());
        $this->assertEmpty($this->pagination->getPreviousNode());
        $this->assertEmpty($this->pagination->getFirstNode());
        $this->assertEmpty($this->pagination->getLastNode());
        $this->assertEmpty($this->pagination->getNextNode());
        $this->assertFalse($this->pagination->getCanShowNext());
        $this->assertFalse($this->pagination->getCanShowFirst());
        $this->assertFalse($this->pagination->getCanShowLast());
        $this->assertEquals(-10, $this->pagination->getRangeStart());
        $this->assertEquals(0, $this->pagination->getRangeEnd());
        $this->assertFalse($this->pagination->getCanShowPreviousJump());
        $this->assertFalse($this->pagination->getCanShowNextJump());
    }

    /**
     * General asserts and conditions for initPagerFlags tests
     *
     * @param int $currentNode
     * @param bool $showNext
     * @param bool $showPrev
     */
    protected function generalTestInitPagerFlags($currentNode, $showNext, $showPrev)
    {
        $firstNode = 1;
        $lastNode = 10;

        $nodes = $this->getParentNodeChildren();
        $this->nodeMock->expects($this->once())
            ->method('setCollectActivePagesOnly')
            ->with(true)
            ->willReturnSelf();
        $this->nodeMock->expects($this->once())
            ->method('getParentNodeChildren')
            ->willReturn($nodes);
        $this->nodeMock->expects($this->any())
            ->method('getId')
            ->willReturn($currentNode);

        $this->assertSame($nodes, $this->pagination->getNodes());
        $this->assertEquals($firstNode, $this->pagination->getFirstNode()->getId());
        $this->assertEquals($lastNode, $this->pagination->getLastNode()->getId());
        $this->assertEquals($showNext, $this->pagination->canShowNext());
        $this->assertEquals($showPrev, $this->pagination->canShowPrevious());
    }

    /**
     * Get array that contain NodeMock objects
     *
     * @return array
     */
    protected function getParentNodeChildren()
    {
        $nodes = [];
        for ($i = 1; $i <= 10; $i++) {
            /** @var Node|MockObject $node */
            $node = $this->getMockBuilder(Node::class)
                ->addMethods(['setPageNumber'])
                ->onlyMethods(['getId'])
                ->disableOriginalConstructor()
                ->getMock();
            $node->expects($this->any())
                ->method('getId')
                ->willReturn($i);
            $node->expects($this->any())
                ->method('setPageNumber')
                ->with($i);
            $nodes[] = $node;
        }

        return $nodes;
    }

    public function testInitPagerFlagsCurrentFirst()
    {
        $currentNode = 1;
        $nextNode = 2;
        $showNext = true;
        $showPrev = false;

        $this->generalTestInitPagerFlags($currentNode, $showNext, $showPrev);
        $this->assertEquals($nextNode, $this->pagination->getNextNode()->getId());
        $this->assertEmpty($this->pagination->getPreviousNode());
    }

    public function testInitPagerFlagsCurrentLast()
    {
        $currentNode = 10;
        $prevNode = 9;
        $showNext = false;
        $showPrev = true;

        $this->generalTestInitPagerFlags($currentNode, $showNext, $showPrev);
        $this->assertEmpty($this->pagination->getNextNode());
        $this->assertEquals($prevNode, $this->pagination->getPreviousNode()->getId());
    }

    public function testInitPagerFlagsCurrentInMiddle()
    {
        $currentNode = 5;
        $prevNode = 4;
        $nextNode = 6;
        $showNext = true;
        $showPrev = true;

        $this->generalTestInitPagerFlags($currentNode, $showNext, $showPrev);
        $this->assertEquals($nextNode, $this->pagination->getNextNode()->getId());
        $this->assertEquals($prevNode, $this->pagination->getPreviousNode()->getId());
    }

    /**
     * @param int|null $currentNode
     * @return void
     */
    protected function prepareNodeMock($currentNode = null)
    {
        $this->nodeMock->expects($this->once())
            ->method('setCollectActivePagesOnly')
            ->with(true)
            ->willReturnSelf();
        $this->nodeMock->expects($this->once())
            ->method('getParentNodeChildren')
            ->willReturn($this->getParentNodeChildren());
        $this->nodeMock->expects($this->any())
            ->method('getId')
            ->willReturn($currentNode);
    }

    /**
     * @param int $frame
     * @param int $currentNode
     * @param int $start
     * @param int $end
     * @param bool $showFirst
     * @param bool $showLast
     * @dataProvider calculatePagesFrameRangeDataProvider
     */
    public function testCalculatePagesFrameRange($frame, $currentNode, $start, $end, $showFirst, $showLast)
    {
        $this->prepareNodeMock($currentNode);

        $this->pagination->setData('frame', $frame);
        $this->pagination->getNodes();

        $this->assertEquals($showFirst, $this->pagination->canShowFirst());
        $this->assertEquals($showLast, $this->pagination->canShowLast());
        $this->assertEquals($start, $this->pagination->getRangeStart());
        $this->assertEquals($end, $this->pagination->getRangeEnd());
    }

    /**
     * @return array
     */
    public function calculatePagesFrameRangeDataProvider()
    {
        return [
            ['frame' => 0, 'currentNode' => 1, 'start' => 0, 'end' => 10, 'showFirst' => false, 'showLast' => false],
            ['frame' => 10, 'currentNode' => 1, 'start' => 0, 'end' => 10, 'showFirst' => false, 'showLast' => false],
            ['frame' => 10, 'currentNode' => 10, 'start' => 0, 'end' => 10, 'showFirst' => false, 'showLast' => false],
            ['frame' => 10, 'currentNode' => 5, 'start' => 0, 'end' => 10, 'showFirst' => false, 'showLast' => false],
            ['frame' => 5, 'currentNode' => 0, 'start' => 0, 'end' => 5, 'showFirst' => false, 'showLast' => true],
            ['frame' => 5, 'currentNode' => 9, 'start' => 5, 'end' => 10, 'showFirst' => true, 'showLast' => false],
            ['frame' => 5, 'currentNode' => 5, 'start' => 2, 'end' => 7, 'showFirst' => true, 'showLast' => true],
        ];
    }

    /**
     * @param int $currentNode
     * @param int $frame
     * @param int $jump
     * @param int $jumpNode
     * @dataProvider calculateAndInitJumpNextDataProvider
     */
    public function testCalculateAndInitJumpNext($currentNode, $frame, $jump, $jumpNode)
    {
        $this->prepareNodeMock($currentNode);

        $this->pagination->setData('frame', $frame);
        $this->pagination->setData('jump', $jump);
        $this->pagination->getNodes();

        $this->assertTrue($this->pagination->canShowNextJump());
        $this->assertEquals($jumpNode, $this->pagination->getNextJumpNode()->getId());
    }

    /**
     * @return array
     */
    public function calculateAndInitJumpNextDataProvider()
    {
        return [
            ['currentNode' => 1, 'frame' => 5, 'jump' => 1, 'jumpNode' => 6],
            ['currentNode' => 1, 'frame' => 4, 'jump' => 3, 'jumpNode' => 7],
        ];
    }

    /**
     * @param int $currentNode
     * @param int $frame
     * @param int $jump
     * @param int $jumpNode
     * @dataProvider calculateAndInitJumpPrevDataProvider
     */
    public function testCalculateAndInitJumpPrev($currentNode, $frame, $jump, $jumpNode)
    {
        $this->prepareNodeMock($currentNode);

        $this->pagination->setData('frame', $frame);
        $this->pagination->setData('jump', $jump);
        $this->pagination->getNodes();

        $this->assertTrue($this->pagination->canShowPreviousJump());
        $this->assertEquals($jumpNode, $this->pagination->getPreviousJumpNode()->getId());
    }

    /**
     * @return array
     */
    public function calculateAndInitJumpPrevDataProvider()
    {
        return [
            ['currentNode' => 9, 'frame' => 5, 'jump' => 1, 'jumpNode' => 5],
            ['currentNode' => 9, 'frame' => 4, 'jump' => 3, 'jumpNode' => 4],
        ];
    }

    public function testGetNodesInRange()
    {
        $currentNode = 5;
        $frame = 3;

        $this->prepareNodeMock($currentNode);
        $this->pagination->setData('frame', $frame);
        $range = $this->pagination->getNodesInRange();
        $this->assertCount($frame, $range);
        $this->assertEquals($currentNode, $range[1]->getId());
    }
}
