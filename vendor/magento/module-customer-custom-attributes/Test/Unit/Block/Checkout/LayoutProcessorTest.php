<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Block\Checkout;

use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\ResourceModel\Form\Attribute;
use Magento\CustomerCustomAttributes\Block\Checkout\LayoutProcessor;
use Magento\Ui\Component\Form\AttributeMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LayoutProcessorTest extends TestCase
{
    /**
     * @var LayoutProcessor
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $merger;

    /**
     * @var MockObject
     */
    protected $attributeMapper;

    /**
     * @var MockObject
     */
    protected $metadataMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->merger = $this->createMock(AttributeMerger::class);
        $this->attributeMapper = $this->createMock(AttributeMapper::class);
        $this->metadataMock = $this->createMock(AttributeMetadataDataProvider::class);

        $this->model = new LayoutProcessor(
            $this->metadataMock,
            $this->attributeMapper,
            $this->merger
        );
    }

    /**
     * Test layout processor
     *
     * @return void
     */
    public function testProcess(): void
    {
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->addMethods(['getIsUserDefined', 'getAttributeCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $jsLayout = [];
        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['payments-list']['children']['free_method']['children']
        ['form-fields']['children'] = [
            'fieldOne' => [
                'param' => 'value',
            ],
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['payments-list']['children']['free_method']
        ['dataScopePrefix'] = 'freeshipping';

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = [
            'fieldOne' => ['param' => 'value'],
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
        ['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children'] = [
            'customAttribute' => ['param' => 'value'],
        ];

        $this->metadataMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(true);
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn('code');
        $this->attributeMapper->expects($this->once())->method('map')->with($attributeMock);
        $this->merger->expects($this->exactly(2))->method('merge');

        $this->model->process($jsLayout);
    }
}
