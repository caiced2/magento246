<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Product\Rule\Action;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct;
use Magento\TargetRule\Model\ResourceModel\Index;
use PHPUnit\Framework\TestCase;

/**
 * Class for test clean deleted product
 */
class CleanDeleteProductTest extends TestCase
{
    /** @var ObjectManager */
    private $objectManager;

    /**
     * @var CleanDeleteProduct
     */
    protected $_model;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->_model = $this->objectManager->getObject(
            CleanDeleteProduct::class
        );
    }

    public function testEmptyIds()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('We can\'t rebuild the index for an undefined product.');
        $this->_model->execute(null);
    }

    /**
     * Test clean deleted product
     */
    public function testCleanDeleteProduct()
    {
        $ruleFactoryMock = $this->createPartialMock(\Magento\TargetRule\Model\RuleFactory::class, ['create']);

        $collectionFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $productCollectionFactoryMock = $this->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resourceMock = $this->createMock(Index::class);

        $resourceMock->expects($this->once())
            ->method('deleteProductFromIndex')
            ->willReturn(1);

        $storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $timezoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $model = $this->objectManager->getObject(
            CleanDeleteProduct::class,
            [
                'ruleFactory' => $ruleFactoryMock,
                'ruleCollectionFactory' => $collectionFactoryMock,
                'resource' => $resourceMock,
                'storeManager' => $storeManagerMock,
                'localeDate' => $timezoneMock,
                'productCollectionFactory' => $productCollectionFactoryMock
            ]
        );

        $model->execute(2);
    }
}
