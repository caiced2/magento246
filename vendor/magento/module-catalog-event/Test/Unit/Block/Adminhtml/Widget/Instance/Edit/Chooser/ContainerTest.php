<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Adminhtml\Widget\Instance\Edit\Chooser;

use Magento\Widget\Block\Adminhtml\Widget\Instance\Edit\Chooser\Container;
use Magento\Widget\Test\Unit\Block\Adminhtml\Widget\Instance\Edit\Chooser\AbstractContainerTest;

/**
 * Test for Magento\Widget\Block\Adminhtml\Widget\Instance\Edit\Chooser\Container
 *
 * Defined here because it covers functionality that should not be exposed
 */
class ContainerTest extends AbstractContainerTest
{
    /**
     * @var \Magento\Widget\Block\Adminhtml\Widget\Instance\Edit\Chooser\Container
     */
    protected $containerBlock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->containerBlock = new Container(
            $this->contextMock,
            $this->layoutProcessorFactoryMock,
            $this->themeCollectionFactoryMock,
            [],
            $this->pageLayoutConfigBuilderMock
        );
    }

    /**
     * @return void
     */
    public function testToHtmlCatalogEventsCarouselVirtualProduct()
    {
        $pageLayoutProcessorContainers = [
            'after.body.start' => 'Page Top',
            'columns.top' => 'Before Main Columns',
            'main' => 'Main Content Container',
            'page.bottom' => 'Before Page Footer Container',
            'before.body.end' => 'Page Bottom',
            'header.container' => 'Page Header Container',
            'page.top' => 'After Page Header',
            'footer-container' => 'Page Footer Container',
            'sidebar.main' => 'Sidebar Main',
            'sidebar.additional' => 'Sidebar Additional'
        ];
        $layoutProcessorContainers = [
            'product.info.virtual.extra' => 'Product Extra Info',
            'header.panel' => 'Page Header Panel',
            'header-wrapper' => 'Page Header',
            'top.container' => 'After Page Header Top',
            'content.top' => 'Main Content Top',
            'content' => 'Main Content Area',
            'content.aside' => 'Main Content Aside',
            'content.bottom' => 'Main Content Bottom',
            'page.bottom' => 'Before Page Footer',
            'footer' => 'Page Footer',
            'cms_footer_links_container' => 'CMS Footer Links'
        ];
        $allowedContainers = ['sidebar.main', 'content', 'sidebar.additional'];
        $expectedHtml = '<select name="block" id="" class="required-entry select" title="" '
            . 'onchange="WidgetInstance.loadSelectBoxByType(\'block_template\', this.up(\'div.group_container\'), '
            . 'this.value)"><option value="" selected="selected" >-- Please Select --</option><option value="content" >'
            . 'Main Content Area</option><option value="sidebar.additional" >Sidebar Additional</option>'
            . '<option value="sidebar.main" >Sidebar Main</option></select>';

        $this->eventManagerMock->expects($this->exactly(2))->method('dispatch')->willReturn(true);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->willReturn(false);

        $this->themeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->themeCollectionMock);
        $this->themeCollectionMock->expects($this->once())->method('getItemById')->willReturn($this->themeMock);

        $this->layoutProcessorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->layoutMergeMock);
        $this->layoutMergeMock->expects($this->exactly(2))->method('addPageHandles')->willReturn(true);
        $this->layoutMergeMock->expects($this->exactly(2))->method('load')->willReturnSelf();
        $this->layoutMergeMock->expects($this->any())->method('addHandle')->willReturnSelf();
        $this->layoutMergeMock->expects($this->any())->method('getContainers')->willReturnOnConsecutiveCalls(
            $pageLayoutProcessorContainers,
            $layoutProcessorContainers
        );

        $this->containerBlock->setAllowedContainers($allowedContainers);
        $this->containerBlock->setValue('');

        $this->escaperMock->expects($this->any())->method('escapeHtml')->willReturnMap(
            [
                ['', null, ''],
                ['-- Please Select --', null, '-- Please Select --'],
                ['content', null, 'content'],
                ['Main Content Area', null, 'Main Content Area'],
                ['sidebar.additional', null, 'sidebar.additional'],
                ['Sidebar Additional', null, 'Sidebar Additional'],
                ['sidebar.main', null, 'sidebar.main'],
                ['Sidebar Main', null, 'Sidebar Main']
            ]
        );

        $this->assertEquals($expectedHtml, $this->containerBlock->toHtml());
    }
}
