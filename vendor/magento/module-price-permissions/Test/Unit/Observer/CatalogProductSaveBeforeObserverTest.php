<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Observer;

use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PricePermissions\Helper\Data;
use Magento\PricePermissions\Observer\CatalogProductSaveBeforeObserver;
use Magento\PricePermissions\Observer\ObserverData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogProductSaveBeforeObserverTest extends TestCase
{
    /**
     * @var CatalogProductSaveBeforeObserver
     */
    protected $_observer;

    /**
     * @var Observer
     */
    protected $_varienObserver;

    /**
     * @var Extended
     */
    protected $_block;

    /**
     * @var ObserverData|MockObject
     */
    protected $observerData;

    protected function setUp(): void
    {
        $this->_block = $this->getMockBuilder(Grid::class)
            ->addMethods(
                ['setCanReadPrice', 'setCanEditPrice', 'setTabData', 'setDefaultProductPrice', 'getForm', 'getGroup']
            )
            ->onlyMethods(['getNameInLayout', 'getMassactionBlock', 'getChildBlock', 'getParentBlock'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_varienObserver = $this->getMockBuilder(Observer::class)
            ->addMethods(['getBlock'])
            ->onlyMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_varienObserver->expects($this->any())->method('getBlock')->willReturn($this->_block);
    }

    public function testCatalogProductSaveBefore()
    {
        $helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCanAdminEditProductStatus'])->getMock();
        $helper->expects($this->once())->method('getCanAdminEditProductStatus')->willReturn(false);

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew', 'setStatus', 'setPrice'])->getMock();
        $product->expects($this->exactly(2))->method('isObjectNew')->willReturn(true);
        $product->expects($this->once())->method('setPrice')->with(100);
        $product->expects($this->once())->method('setStatus')
            ->with(Status::STATUS_DISABLED)
            ->willReturnSelf();

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataObject'])->getMock();
        $event->expects($this->once())->method('getDataObject')->willReturn($product);
        $this->_varienObserver->expects($this->once())->method('getEvent')->willReturn($event);

        $this->observerData = $this->getMockBuilder(ObserverData::class)
            ->setMethods(
                [
                    'setCanEditProductStatus',
                    'getDefaultProductPriceString',
                    'isCanReadProductPrice',
                    'isCanEditProductStatus'
                ]
            )->disableOriginalConstructor()
            ->getMock();

        $this->observerData->expects($this->once())->method('setCanEditProductStatus')->with(false);
        $this->observerData->expects($this->once())->method('isCanReadProductPrice')->willReturn(false);
        $this->observerData->expects($this->once())->method('isCanEditProductStatus')->willReturn(false);
        $this->observerData->expects($this->once())->method('getDefaultProductPriceString')->willReturn(100);

        /** @var CatalogProductSaveBeforeObserver $model */
        $model = (new ObjectManager($this))
            ->getObject(
                CatalogProductSaveBeforeObserver::class,
                [
                    'pricePermData' => $helper,
                    'observerData' => $this->observerData,
                ]
            );

        $model->execute($this->_varienObserver);
    }
}
