<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Banner\Block\Widget\Banner;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\GoogleTagManager\Block\ListJson;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Plugin\Layout;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
    /** @var Layout */
    protected $layout;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->layout = $this->objectManagerHelper->getObject(
            Layout::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * @param bool $available
     * @param mixed $expectsBanner
     * @param mixed $expects
     *
     * @dataProvider afterCreateBlockDataProvider
     */
    public function testAfterCreateBlock($available, $expectsBanner, $expects)
    {
        $result = $this->createMock(Banner::class);

        $block = $this->createMock(ListJson::class);
        $block->expects($expectsBanner)->method('appendBannerBlock')->with($result);

        $subject = $this->getMockForAbstractClass(LayoutInterface::class);
        $subject->expects($expects)->method('getBlock')->with('banner_impression')->willReturn($block);

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn($available);

        $this->assertSame($result, $this->layout->afterCreateBlock($subject, $result));
    }

    public function afterCreateBlockDataProvider()
    {
        return [
            [true, $this->once(), $this->once()],
            [false, $this->never(), $this->never()]
        ];
    }

    public function testAfterCreateBlockForNonBanners()
    {
        $result = $this->getMockForAbstractClass(BlockInterface::class);

        $subject = $this->getMockForAbstractClass(LayoutInterface::class);
        $subject->expects($this->never())->method('getBlock');

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(true);

        $this->assertSame($result, $this->layout->afterCreateBlock($subject, $result));
    }
}
