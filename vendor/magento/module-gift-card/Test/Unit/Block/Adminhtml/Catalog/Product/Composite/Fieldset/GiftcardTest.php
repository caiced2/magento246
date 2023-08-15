<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Catalog\Product\Composite\Fieldset;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Block\Adminhtml\Catalog\Product\Composite\Fieldset\Giftcard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $block;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistry;

    protected function setUp(): void
    {
        $this->coreRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Giftcard::class,
            [
                'coreRegistry' => $this->coreRegistry
            ]
        );
    }

    public function testGetIsLastFieldsetWithData()
    {
        $this->block->setData('is_last_fieldset', true);

        $this->assertTrue($this->block->getIsLastFieldset());
    }

    public function testGetIsLastFieldset()
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getTypeInstance', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $typeInstance = $this->getMockBuilder(\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::class)
            ->setMethods(['getStoreFilter'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->block->setData('product', $product);

        $product->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($typeInstance);
        $typeInstance->expects($this->once())
            ->method('getStoreFilter')
            ->with($product)
            ->willReturn(true);
        $product->expects($this->once())
            ->method('getOptions')
            ->willReturn(null);

        $this->assertTrue($this->block->getIsLastFieldset());
    }
}
