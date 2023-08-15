<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\PricePermissions\Observer\ObserverData;
use Magento\PricePermissions\Observer\ViewBlockAbstractToHtmlBeforeObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewBlockAbstractToHtmlBeforeObserverTest extends TestCase
{
    /**
     * @var ObserverData|MockObject
     */
    protected $observerData;

    /**
     * @covers \Magento\PricePermissions\Observer\ViewBlockAbstractToHtmlBeforeObserver::execute
     */
    public function testViewBlockAbstractToHtmlBefore()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew', 'getIsRecurring'])
            ->getMock();
        $product->expects($this->any())->method('isObjectNew')->willReturn(false);
        $product->expects($this->any())->method('getIsRecurring')->willReturn(true);

        $productFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $productFactory->expects($this->any())->method('create')->willReturn($product);

        $coreRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();
        $coreRegistry->expects($this->any())->method('registry')->with('product')->willReturn($product);

        $this->observerData = $this->createMock(ObserverData::class);
        $this->observerData->expects($this->any())->method('isCanEditProductPrice')->willReturn(false);
        $this->observerData->expects($this->any())->method('isCanReadProductPrice')->willReturn(false);

        $model = (new ObjectManager($this))
            ->getObject(
                ViewBlockAbstractToHtmlBeforeObserver::class,
                [
                    'coreRegistry' => $coreRegistry,
                    'productFactory' => $productFactory,
                    'observerData' => $this->observerData,
                ]
            );
        $block = $this->getMockBuilder(
            AbstractBlock::class
        )->disableOriginalConstructor()
            ->setMethods(
                [
                    'getNameInLayout',
                    'setProductEntity',
                    'setIsReadonly',
                    'addConfigOptions',
                    'addFieldDependence',
                    'setCanEditPrice'
                ]
            )->getMock();
        $observer = $this->getMockBuilder(
            Observer::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getBlock']
            )->getMock();
        $observer->expects($this->any())->method('getBlock')->willReturn($block);

        $nameInLayout = 'adminhtml.catalog.product.edit.tab.attributes';
        $block->expects($this->any())->method('getNameInLayout')->willReturn($nameInLayout);
        $block->expects($this->once())->method('setCanEditPrice')->with(false);

        $model->execute($observer);
    }
}
