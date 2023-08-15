<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart;
use Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Amount;
use Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Itemsquantity;
use Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Productsquantity;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class ShoppingcartTest extends TestCase
{
    /**
     * @var Shoppingcart
     */
    protected $model;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Segment
     */
    protected $resourceSegment;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var Amount
     */
    protected $cartAmount;

    /**
     * @var Itemsquantity
     */
    protected $cartItemsquantity;

    /**
     * @var Productsquantity
     */
    protected $cartProductsquantity;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment = $this->createMock(Segment::class);
        $this->conditionFactory = $this->createMock(ConditionFactory::class);

        $this->cartAmount = $this->createMock(
            Amount::class
        );
        $this->cartItemsquantity = $this->createMock(
            Itemsquantity::class
        );
        $this->cartProductsquantity = $this->createMock(
            Productsquantity::class
        );

        $this->model = new Shoppingcart(
            $this->context,
            $this->resourceSegment,
            $this->conditionFactory
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->context,
            $this->resourceSegment,
            $this->conditionFactory,
            $this->cartAmount,
            $this->cartItemsquantity,
            $this->cartProductsquantity
        );
    }

    public function testGetNewChildSelectOptions()
    {
        $amountOptions = ['test_amount_options'];
        $itemsquantityOptions = ['test_itemsquantity_options'];
        $productsquantityOptions = ['test_productsquantity_options'];

        $this->cartAmount
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($amountOptions);

        $this->cartItemsquantity
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($itemsquantityOptions);

        $this->cartProductsquantity
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($productsquantityOptions);

        $this->conditionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnMap([
                ['Shoppingcart\Amount', [], $this->cartAmount],
                ['Shoppingcart\Itemsquantity', [], $this->cartItemsquantity],
                ['Shoppingcart\Productsquantity', [], $this->cartProductsquantity],
            ]);

        $result = $this->model->getNewChildSelectOptions();

        $this->assertIsArray($result);
        $this->assertEquals(
            [
                'value' => [
                    $amountOptions,
                    $itemsquantityOptions,
                    $productsquantityOptions,
                ],
                'label' => __('Shopping Cart'),
                'available_in_guest_mode' => true,
            ],
            $result
        );
    }
}
