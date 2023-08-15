<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Block\Adminhtml\Promo\Catalog;

use Magento\Backend\Block\Widget\Button\Item;
use Magento\CatalogRule\Block\Adminhtml\Promo\Catalog;
use Magento\CatalogRuleStaging\Block\Adminhtml\Promo\Catalog\Plugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testBeforeCanRender()
    {
        $applyButtonId = 'apply_rules';
        $blockMock = $this->getMockBuilder(Catalog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $buttonMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        /**
         * @var Plugin $plugin
         */
        $plugin = $helper->getObject(Plugin::class);
        $buttonMock->expects($this->exactly(2))->method('getId')
            ->willReturnOnConsecutiveCalls($applyButtonId, $applyButtonId, 'another_button');
        $blockMock->expects($this->once())->method('removeButton')->with($applyButtonId);
        $this->assertNull($plugin->beforeCanRender($blockMock, $buttonMock));
    }
}
