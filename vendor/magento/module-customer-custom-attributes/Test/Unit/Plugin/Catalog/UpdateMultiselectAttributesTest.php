<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Plugin\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as ProductHelper;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CustomerCustomAttributes\Plugin\Catalog\UpdateMultiselectAttributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for checking multiselect attributes for product data
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class UpdateMultiselectAttributesTest extends TestCase
{
    /**
     * @var UpdateMultiselectAttributes
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
            ->disableOriginalConstructor()
            ->addMethods(['getAttributes', 'setData', 'getData'])
            ->getMockForAbstractClass();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(UpdateMultiselectAttributes::class);
    }

    /**
     * Test case for after initialize form data with product data provider
     *
     * @param string $customAttributeKeyName
     * @param array $productData
     * @param array $expectedProductData
     * @dataProvider productDataProvider
     */
    public function testAfterInitializeFromData(
        string $customAttributeKeyName,
        array $productData,
        array $expectedProductData
    ): void {
        $this->productMock->expects($this->any())
            ->method('getData')
            ->willReturn($productData);
        $this->productMock->expects($this->any())
            ->method('getAttributes')
            ->willReturn($productData['custom_attributes']);

        $result = $this->model->afterInitializeFromData($this->productHelperMock, $this->productMock);
        $this->assertSame(
            $expectedProductData[$customAttributeKeyName],
            $result->getData()[$customAttributeKeyName]
        );
    }

    /**
     * Data provider for product test
     *
     * @return array
     */
    public function productDataProvider(): array
    {
        $multiSelectAttributeMock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFrontendInput'])
            ->getMockForAbstractClass();
        $multiSelectAttributeMock->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn('multiselect');
        $selectAttributeMock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFrontendInput'])
            ->getMockForAbstractClass();
        $selectAttributeMock->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn('select');

        return [
            'test with multiselect NULL attributes' =>
                [
                'customAttributeKeyName' => 'multiselectAttribute',
                'productData' =>
                    [
                        'name' => 'product1',
                        'sku' => 'product1',
                        'custom_attributes' => ['multiselectAttribute' => $multiSelectAttributeMock],
                        'multiselectAttribute' => null
                    ],
                'expectedProductData' =>
                    [
                        'name' => 'product1',
                        'sku' => 'product1',
                        'multiselectAttribute' => null
                    ]
                ],
            'test with multiselect NOT NULL attributes' =>
                [
                    'customAttributeKeyName' => 'multiselectAttribute',
                    'productData' =>
                        [
                            'name' => 'product1',
                            'sku' => 'product1',
                            'custom_attributes' =>
                                [
                                    'multiselectAttribute' => $multiSelectAttributeMock,
                                    'select' => $selectAttributeMock
                                ],
                            'multiselectAttribute' => 125,
                            'select' => null
                        ],
                    'expectedProductData' =>
                        [
                            'name' => 'product1',
                            'sku' => 'product1',
                            'multiselectAttribute' => 125,
                            'select' => 456
                        ]
                ],
            'test with select attributes' =>
                [
                    'customAttributeKeyName' => 'select',
                    'productData' =>
                        [
                            'name' => 'product2',
                            'sku' => 'product2',
                            'custom_attributes' => ['select' => $selectAttributeMock],
                            'select' => 123
                        ],
                    'expectedProductData' =>
                        [
                            'name' => 'product2',
                            'sku' => 'product2',
                            'select' => 123
                        ]
                ]
        ];
    }
}
