<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Customer;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Newsletter;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Storecredit;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    /**
     * @var Customer
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
     * @var Attributes
     */
    protected $customerAttributes;

    /**
     * @var Newsletter
     */
    protected $customerNewsletter;

    /**
     * @var Storecredit
     */
    protected $customerStorecredit;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment = $this->createMock(Segment::class);
        $this->conditionFactory = $this->createMock(ConditionFactory::class);

        $this->customerAttributes = $this->createMock(
            Attributes::class
        );
        $this->customerNewsletter = $this->createMock(
            Newsletter::class
        );
        $this->customerStorecredit = $this->createMock(
            Storecredit::class
        );

        $this->model = new Customer(
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
            $this->customerAttributes,
            $this->customerNewsletter,
            $this->customerStorecredit
        );
    }

    public function testGetNewChildSelectOptions()
    {
        $attributesOptions = ['test_attributes_options'];
        $newsletterOptions = ['test_newsletter_options'];
        $storecreditOptions = ['test_storecredit_options'];

        $this->customerAttributes
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($attributesOptions);

        $this->customerNewsletter
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($newsletterOptions);

        $this->customerStorecredit
            ->expects($this->once())
            ->method('getNewChildSelectOptions')
            ->willReturn($storecreditOptions);

        $this->conditionFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnMap([
                ['Customer\Attributes', [], $this->customerAttributes],
                ['Customer\Newsletter', [], $this->customerNewsletter],
                ['Customer\Storecredit', [], $this->customerStorecredit],
            ]);

        $result = $this->model->getNewChildSelectOptions();

        $this->assertIsArray($result);
        $this->assertEquals(
            [
                'value' => array_merge($attributesOptions, $newsletterOptions, $storecreditOptions),
                'label' => __('Customer'),
            ],
            $result
        );
    }
}
