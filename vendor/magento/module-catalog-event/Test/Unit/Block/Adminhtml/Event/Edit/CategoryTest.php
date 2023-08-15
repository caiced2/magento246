<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Adminhtml\Event\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Category;
use Magento\CatalogEvent\Helper\Adminhtml\Event;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

/**
 * Unit test for Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Category
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTest extends TestCase
{
    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Tree|MockObject
     */
    protected $treeMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var MockObject
     */
    protected $categoryFactoryMock;

    /**
     * @var EncoderInterface|MockObject
     */
    protected $encoderMock;

    /**
     * @var Event|MockObject
     */
    protected $catalogEventAdminhtmlHelperMock;

    /**
     * @var Escaper|MockObject
     */
    private $escaperMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->treeMock = $this->getMockBuilder(Tree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->encoderMock = $this->getMockBuilder(EncoderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->catalogEventAdminhtmlHelperMock = $this->getMockBuilder(
            Event::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaperMock = $this->createPartialMock(Escaper::class, ['escapeHtml']);

        /** @var UrlInterface|MockObject $urlBuilder */
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $this->contextMock->expects($this->once())->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->contextMock->expects($this->once())->method('getEscaper')->willReturn($this->escaperMock);

        $this->category = new Category(
            $this->contextMock,
            $this->treeMock,
            $this->registryMock,
            $this->categoryFactoryMock,
            $this->encoderMock,
            $this->catalogEventAdminhtmlHelperMock
        );
    }

    /**
     * Test Get Tree Url
     *
     * @return void
     */
    public function testGetLoadTreeUrl(): void
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/*/categoriesJson', [])
            ->willReturn('result');

        $this->assertEquals(
            'result',
            $this->category->getLoadTreeUrl()
        );
    }

    /**
     * Test Get Category Nodes
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetNodesArray(): void
    {
        $node = $this->createPartialMock(
            Node::class,
            ['getName', 'hasChildren', 'getIsActive', 'getId']
        );

        $name = 'test';
        $testData = [
            'id' => 0,
            'children_count' => 0,
            'disabled' => true,
            'name' => $name,
            'product_count' => 0,
            'cls' => 'active-category em',
            'expanded' => false
        ];

        $node->expects($this->exactly(1))->method('getName')->willReturn($name);
        $node->expects($this->exactly(1))->method('getIsActive')->willReturn(true);
        $node->expects($this->exactly(1))->method('hasChildren')->willReturn(false);
        $node->method('getId')->willReturn('test_id');
        $this->escaperMock
            ->expects($this->exactly(1))
            ->method('escapeHtml')
            ->with($name)
            ->willReturn($name);

        $refClass = new \ReflectionClass(Category::class);
        $refMethod = $refClass->getMethod('_getNodesArray');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->category, $node);
        $this->assertEquals($testData, $result);
    }
}
