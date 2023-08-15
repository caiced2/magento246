<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Block\Hierarchy;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\VersionsCms\Block\Hierarchy\Head;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\CurrentNodeResolverInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HeadTest extends TestCase
{
    /**
     * @var Hierarchy|MockObject
     */
    protected $cmsHierarchy;

    /**
     * @var Node|MockObject
     */
    protected $chapter;

    /**
     * @var Node|MockObject
     */
    protected $section;

    /**
     * @var Node|MockObject
     */
    protected $next;

    /**
     * @var Node|MockObject
     */
    protected $prev;

    /**
     * @var Node|MockObject
     */
    protected $first;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layout;

    /**
     * @var Node|MockObject
     */
    protected $node;

    /**
     * @var Config|MockObject
     */
    protected $pageConfig;

    /**
     * @var Head
     */
    protected $head;

    /**
     * @var CurrentNodeResolverInterface|MockObject
     */
    private $currentNodeResolver;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->cmsHierarchy = $this->createMock(Hierarchy::class);

        $this->chapter = $this->createMock(Node::class);
        $this->section = $this->createMock(Node::class);
        $this->next = $this->createMock(Node::class);
        $this->prev = $this->createMock(Node::class);
        $this->first = $this->createMock(Node::class);

        $this->pageConfig = $this->createMock(Config::class);
        $this->node = $this->createMock(Node::class);
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);

        $this->currentNodeResolver = $this->getMockBuilder(CurrentNodeResolverInterface::class)
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        /** @var Head $head */
        $this->head = (new ObjectManager($this))
            ->getObject(
                Head::class,
                [
                    'cmsHierarchy' => $this->cmsHierarchy,
                    'pageConfig' => $this->pageConfig,
                    'currentNodeResolver' => $this->currentNodeResolver,
                    'context' => $this->context
                ]
            );
    }

    /**
     * @return void
     */
    public function testPrepareLayoutMetaDataEnabledAndNodeExistsShouldAddRemotePageAssets(): void
    {
        $chapterUrl = 'chapter/url';
        $sectionUrl = 'section/url';
        $nextUrl = 'next/url';
        $prevUrl = 'prev/url';
        $firstUrl = 'first/url';

        $treeMetaData = [
            'meta_cs_enabled' => true,
            'meta_next_previous' => true,
            'meta_first_last' => true
        ];

        $this->cmsHierarchy->expects($this->once())->method('isMetadataEnabled')->willReturn(true);

        $this->chapter->expects($this->once())->method('getId')->willReturn(1);
        $this->chapter->expects($this->once())->method('getUrl')->willReturn($chapterUrl);

        $this->section->expects($this->once())->method('getId')->willReturn(1);
        $this->section->expects($this->once())->method('getUrl')->willReturn($sectionUrl);

        $this->next->expects($this->once())->method('getId')->willReturn(1);
        $this->next->expects($this->once())->method('getUrl')->willReturn($nextUrl);

        $this->prev->expects($this->once())->method('getId')->willReturn(1);
        $this->prev->expects($this->once())->method('getUrl')->willReturn($prevUrl);

        $this->first->expects($this->once())->method('getId')->willReturn(1);
        $this->first->expects($this->once())->method('getUrl')->willReturn($firstUrl);

        $this->node->expects($this->once())->method('getTreeMetaData')->willReturn($treeMetaData);
        $this->node->expects($this->any())
            ->method('getMetaNodeByType')
            ->willReturnMap(
                [
                    [Node::META_NODE_TYPE_CHAPTER, $this->chapter],
                    [Node::META_NODE_TYPE_SECTION, $this->section],
                    [Node::META_NODE_TYPE_NEXT, $this->next],
                    [Node::META_NODE_TYPE_PREVIOUS, $this->prev],
                    [Node::META_NODE_TYPE_FIRST, $this->first]
                ]
            );

        $this->pageConfig
            ->method('addRemotePageAsset')
            ->withConsecutive(
                [
                    $chapterUrl,
                    '',
                    ['attributes' => ['rel' => Node::META_NODE_TYPE_CHAPTER]]
                ],
                [
                    $sectionUrl,
                    '',
                    ['attributes' => ['rel' => Node::META_NODE_TYPE_SECTION]]
                ],
                [
                    $nextUrl,
                    '',
                    ['attributes' => ['rel' => Node::META_NODE_TYPE_NEXT]]
                ],
                [
                    $prevUrl,
                    '',
                    ['attributes' => ['rel' => Node::META_NODE_TYPE_PREVIOUS]]
                ],
                [
                    $firstUrl,
                    '',
                    ['attributes' => ['rel' => Node::META_NODE_TYPE_FIRST]]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->pageConfig,
                $this->pageConfig,
                $this->pageConfig,
                $this->pageConfig,
                $this->pageConfig
            );

        $this->currentNodeResolver->expects($this->once())
            ->method('get')
            ->with($this->request)
            ->willReturn($this->node);

        $this->assertSame($this->head, $this->head->setLayout($this->layout));
    }
}
