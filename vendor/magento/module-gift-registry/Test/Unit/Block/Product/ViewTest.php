<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Block\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\GiftRegistry\Block\Product\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /**
     * @var View|null
     */
    protected $_block = null;

    /**
     * @var MockObject|null
     */
    protected $_urlBuilder = null;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->_urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $args = ['urlBuilder' => $this->_urlBuilder];
        $this->_block = $helper->getObject(View::class, $args);
    }

    /**
     * @param string $options
     * @param string|null $expectedTemplate
     * @dataProvider setGiftRegistryTemplateDataProvider
     */
    public function testSetGiftRegistryTemplate($options, $expectedTemplate)
    {
        $request = $this->_block->getRequest();
        $request->expects($this->any())->method('getParam')->with('options')->willReturn($options);
        $childBlock = $this->getMockForAbstractClass(
            AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->willReturn($childBlock);
        $this->_block->setGiftRegistryTemplate('test', 'template.phtml');
        $actualTemplate = $childBlock->getTemplate();
        $this->assertSame($expectedTemplate, $actualTemplate);
    }

    /**
     * @return array
     */
    public function setGiftRegistryTemplateDataProvider()
    {
        return [
            'no options' => ['some other option', null],
            'with options' => [View::FLAG, 'template.phtml']
        ];
    }

    public function testSetGiftRegistryTemplateNoBlock()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Could not find block \'test\'');
        $this->_block->setGiftRegistryTemplate('test', 'template.phtml');
    }

    public function testSetGiftRegistryUrl()
    {
        $this->_urlBuilder->expects($this->any())->method('getUrl')->willReturn('some_url');
        $request = $this->_block->getRequest();
        $valueMap = [
            ['options', null, View::FLAG],
            ['entity', null, 'any'],
        ];
        $request->expects($this->any())->method('getParam')->willReturnMap($valueMap);
        $childBlock = $this->getMockForAbstractClass(
            AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->willReturn($childBlock);
        $this->_block->setGiftRegistryUrl('test');
        $actualUrl = $childBlock->getAddToGiftregistryUrl();
        $this->assertSame('some_url', $actualUrl);
    }

    public function testSetGiftRegistryUrlNoOptions()
    {
        $childBlock = $this->getMockForAbstractClass(
            AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->willReturn($childBlock);
        $this->_block->setGiftRegistryUrl('test');
        $actualUrl = $childBlock->getGiftRegistryUrl();
        $this->assertNull($actualUrl);
    }

    public function testSetGiftRegistryUrlNoBlock()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Could not find block \'test\'');
        $this->_block->setGiftRegistryUrl('test');
    }
}
