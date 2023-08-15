<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Order\Address;
use Magento\CustomerSegment\Model\Segment\Condition\Sales;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Purchasedquantity;
use Magento\CustomerSegment\Model\Segment\Condition\Sales\Salesamount;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class SalesTest extends TestCase
{
    /**
     * @var Sales
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

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment =
            $this->createMock(Segment::class);

        $this->model = new Sales(
            $this->context,
            $this->resourceSegment
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->context,
            $this->resourceSegment
        );
    }

    public function testGetNewChildSelectOptions()
    {
        $data = [
            'value' => [
                [
                    'value' => Address::class,
                    'label' => __('Order Address'),
                ],
                [
                    'value' => Salesamount::class,
                    'label' => __('Sales Amount'),
                ],
                [
                    'value' => Ordersnumber::class,
                    'label' => __('Number of Orders'),
                ],
                [
                    'value' => Purchasedquantity::class,
                    'label' => __('Purchased Quantity'),
                ],
            ],
            'label' => __('Sales'),
        ];

        $result = $this->model->getNewChildSelectOptions();

        $this->assertIsArray($result);
        $this->assertEquals($data, $result);
    }
}
