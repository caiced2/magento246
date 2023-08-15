<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Block\Adminhtml\Catalog\Product\Edit\Tab\Giftcard;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $block;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistry;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfig;

    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManager::class)
            ->setMethods(['isSingleStoreMode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry = $this->getMockBuilder(Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Giftcard::class,
            [
                'storeManager' => $this->storeManager,
                'coreRegistry' => $this->coreRegistry,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * @dataProvider getScopeValueDataProvider
     * @param boolean $isSingleStore
     * @param string $scope
     * @param string $expectedResult
     */
    public function testGetScopeValue($isSingleStore, $scope, $expectedResult)
    {
        $this->storeManager->expects($this->any())
            ->method('isSingleStoreMode')
            ->willReturn($isSingleStore);

        $this->assertEquals($this->block->getScopeValue($scope), $expectedResult);
    }

    /**
     * @return array
     */
    public function getScopeValueDataProvider()
    {
        return [[true, 'test', ''], [false, 'test', 'value-scope="test"']];
    }

    /**
     * @param $prodId
     * @param $result
     * @dataProvider isNewDataProvider
     */
    public function testIsNew($prodId, $result)
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn($prodId);

        $this->assertEquals($result, $this->block->isNew());
    }

    /**
     * @return array
     */
    public function isNewDataProvider()
    {
        return [
            ['product_id', false],
            [null, true]
        ];
    }

    public function testGetFieldValueForNewProduct()
    {
        $field = 'some_field';

        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('giftcard/general/' . $field, 'store')
            ->willReturn('config_value');

        $this->assertEquals('config_value', $this->block->getFieldValue($field));
    }

    public function testGetFieldValueForExistingProduct()
    {
        $field = 'some_field';

        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getId', 'getDataUsingMethod'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry->expects($this->exactly(2))
            ->method('registry')
            ->with('product')
            ->willReturn($product);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn('product_id');
        $product->expects($this->once())
            ->method('getDataUsingMethod')
            ->with($field)
            ->willReturn('using_method');

        $this->assertEquals('using_method', $this->block->getFieldValue($field));
    }

    public function testGetCardTypes()
    {
        $expected = ['Virtual', 'Physical', 'Combined'];

        $this->assertEquals($expected, $this->block->getCardTypes());
    }
}
