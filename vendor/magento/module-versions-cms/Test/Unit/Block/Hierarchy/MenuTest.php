<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Block\Hierarchy;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context as ElementContext;
use Magento\VersionsCms\Block\Hierarchy\Menu;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    /**
     * @var NodeFactory|MockObject
     */
    private $nodeFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Framework\View\Element\Context|MockObject
     */
    private $contextMock;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolverMock;

    protected function setUp(): void
    {
        $this->nodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(ElementContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->currentNodeResolverMock = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();
    }

    public function testIsBriefIsTrue()
    {
        $nodeParams = [
            'menu_visibility' => 1,
            'menu_brief' => 1,
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getMetadataContextMenuParams')
            ->willReturn($nodeParams);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);

        $blockMock = $this->getBlockMock();
        $this->assertTrue($blockMock->isBrief());
    }

    public function testIsBriefIsFalse()
    {
        $nodeParams = [
            'menu_visibility' => 1,
            'menu_brief' => 0,
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getMetadataContextMenuParams')
            ->willReturn($nodeParams);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);

        $blockMock = $this->getBlockMock();
        $this->assertFalse($blockMock->isBrief());
    }

    public function testIsBriefMenuVisibilityIsFalse()
    {
        $nodeParams = [
            'menu_visibility' => 0,
            'menu_brief' => 1,
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getMetadataContextMenuParams')
            ->willReturn($nodeParams);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);

        $blockMock = $this->getBlockMock();
        $this->assertFalse($blockMock->isBrief());
    }

    public function testIsBriefParamsIsNull()
    {
        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getMetadataContextMenuParams')
            ->willReturn(null);

        $this->currentNodeResolverMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($nodeMock);

        $blockMock = $this->getBlockMock();
        $this->assertFalse($blockMock->isBrief());
    }

    /**
     * Create Hierarchy Menu mock object
     *
     * Helper methods, that provides unified logic of creation of Hierarchy Menu mock object,
     * required to implement test iterations.
     *
     * @return \Magento\VersionsCms\Block\Hierarchy\Menu|MockObject
     */
    private function getBlockMock()
    {
        $blockMock = (new ObjectManager($this))
            ->getObject(
                Menu::class,
                [
                    'nodeFactory' => $this->nodeFactoryMock,
                    'currentNodeResolver' => $this->currentNodeResolverMock,
                    'context' => $this->contextMock,
                ]
            );
        return $blockMock;
    }
}
