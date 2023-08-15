<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductStaging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProductStaging\Block\Adminhtml\Update\Entity\SaveButton;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveButtonTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var SaveButton
     */
    private $saveButtonBlock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->saveButtonBlock = new SaveButton(
            $this->requestMock,
            $this->productRepositoryMock
        );
    }

    /**
     * Check metadata for default product types
     *
     * @dataProvider defaultBehaviourDataProvider
     * @param int $productId
     * @param string $productType
     */
    public function testButtonDefaultMetadata(
        $productId,
        $productType
    ) {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($productId);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getTypeId'])
            ->getMockForAbstractClass();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn($productType);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($productMock);

        $result = $this->saveButtonBlock->getButtonData();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('data_attribute', $result);
        $this->assertArrayHasKey('mage-init', $result['data_attribute']);
        $this->assertArrayHasKey('button', $result['data_attribute']['mage-init']);
        $this->assertArrayHasKey('event', $result['data_attribute']['mage-init']['button']);

        $this->assertEquals('save', $result['data_attribute']['mage-init']['button']['event']);
    }

    /**
     * Provide test data for default product types assertions
     *
     * @return array
     */
    public function defaultBehaviourDataProvider()
    {
        return [
            [
                'product_id' => 1,
                'product_type' => 'test',
            ],
            [
                'product_id' => null,
                'product_type' => null,
            ],
        ];
    }

    /**
     * Check metadata for Configurable product type
     *
     * @dataProvider configurableBehaviourDataProvider
     * @param int $productId
     * @param string $productType
     */
    public function testButtonConfigurableProductMetadata(
        $productId,
        $productType
    ) {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($productId);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getTypeId'])
            ->getMockForAbstractClass();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn($productType);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($productMock);

        $result = $this->saveButtonBlock->getButtonData();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('buttonAdapter', $result['data_attribute']['mage-init']);
        $this->assertArrayHasKey('actions', $result['data_attribute']['mage-init']['buttonAdapter']);
        $this->assertArrayHasKey('targetName', $result['data_attribute']['mage-init']['buttonAdapter']['actions'][0]);
        $this->assertArrayHasKey('actionName', $result['data_attribute']['mage-init']['buttonAdapter']['actions'][0]);

        $this->assertEquals(
            'catalogstaging_update_form.catalogstaging_update_form.configurableVariations',
            $result['data_attribute']['mage-init']['buttonAdapter']['actions'][0]['targetName']
        );
        $this->assertEquals(
            'serializeData',
            $result['data_attribute']['mage-init']['buttonAdapter']['actions'][0]['actionName']
        );
    }

    /**
     * Provide test data for configurable product types assertions
     *
     * @return array
     */
    public function configurableBehaviourDataProvider()
    {
        return [
            [
                'product_id' => 1,
                'product_type' => Type::TYPE_SIMPLE,
            ],
            [
                'product_id' => 2,
                'product_type' => Type::TYPE_VIRTUAL,
            ],
            [
                'product_id' => 3,
                'product_type' => Configurable::TYPE_CODE,
            ],
        ];
    }
}
