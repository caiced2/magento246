<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as ProductHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\Product\Source;
use Magento\Rma\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\HelperPlugin;
use Magento\Rma\Ui\DataProvider\Product\Form\Modifier\Rma;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelperPluginTest extends TestCase
{
    /**
     * @var HelperPlugin
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ProductInterface|MockObject
     */
    protected $productMock;

    /**
     * @var ProductHelper|MockObject
     */
    protected $productHelperMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['setData'])
            ->getMockForAbstractClass();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(HelperPlugin::class, []);
    }

    /**
     * @param array $productData
     * @param array $expectedProductData
     * @dataProvider productDataProvider
     */
    public function testBeforeInitializeFromData(array $productData, array $expectedProductData)
    {
        $result = $this->model->beforeInitializeFromData($this->productHelperMock, $this->productMock, $productData);
        $this->assertSame(
            $expectedProductData,
            $result[1]
        );
    }

    /**
     * @return array
     */
    public function productDataProvider()
    {
        return [
            [
                [Rma::FIELD_IS_RMA_ENABLED => 0, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 1],
                [Rma::FIELD_IS_RMA_ENABLED => Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG]
            ],
            [
                [
                    Rma::FIELD_IS_RMA_ENABLED => Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG,
                    'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0
                ],
                [Rma::FIELD_IS_RMA_ENABLED => 0, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0]
            ],
            [
                [Rma::FIELD_IS_RMA_ENABLED => 0, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0],
                [Rma::FIELD_IS_RMA_ENABLED => 0, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0]
            ],
            [
                [Rma::FIELD_IS_RMA_ENABLED => 1, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 1],
                [Rma::FIELD_IS_RMA_ENABLED => Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG]
            ],
            [
                [Rma::FIELD_IS_RMA_ENABLED => 1, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0],
                [Rma::FIELD_IS_RMA_ENABLED => 1, 'use_config_' . Rma::FIELD_IS_RMA_ENABLED => 0]
            ],
        ];
    }
}
