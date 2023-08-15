<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition;

use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Uptodate;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class UptodateTest extends TestCase
{
    /**
     * @var Uptodate
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
     * @var Segment
     */
    protected $quoteResourceMock;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment = $this->createMock(Segment::class);
        $this->quoteResourceMock = $this->createMock(Quote::class);
        $this->model = new Uptodate(
            $this->context,
            $this->resourceSegment,
            $this->quoteResourceMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->context,
            $this->resourceSegment,
            $this->quoteResourceMock
        );
    }

    public function testGetDefaultOperatorInputByType()
    {
        $result = $this->model->getDefaultOperatorInputByType();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('string', $result);
        $this->assertIsArray($result['string']);
        $this->assertEquals(['==', '!=', '{}', '!{}'], $result['string']);

        $this->assertArrayHasKey('numeric', $result);
        $this->assertIsArray($result['numeric']);
        $this->assertEquals(['>=', '<=', '>', '<'], $result['numeric']);

        $this->assertArrayHasKey('date', $result);
        $this->assertIsArray($result['date']);
        $this->assertEquals(['==', '>=', '<='], $result['date']);

        $this->assertArrayHasKey('select', $result);
        $this->assertIsArray($result['select']);
        $this->assertEquals(['==', '!=', '<=>'], $result['select']);

        $this->assertArrayHasKey('boolean', $result);
        $this->assertIsArray($result['boolean']);
        $this->assertEquals(['==', '!=', '<=>'], $result['boolean']);

        $this->assertArrayHasKey('multiselect', $result);
        $this->assertIsArray($result['multiselect']);
        $this->assertEquals(['==', '!=', '[]', '![]'], $result['multiselect']);

        $this->assertArrayHasKey('grid', $result);
        $this->assertIsArray($result['grid']);
        $this->assertEquals(['()', '!()'], $result['grid']);
    }

    public function testGetDefaultOperatorOptions()
    {
        $result = $this->model->getDefaultOperatorOptions();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('<=', $result);
        $this->assertEquals(__('equals or greater than'), $result['<=']);

        $this->assertArrayHasKey('>=', $result);
        $this->assertEquals(__('equals or less than'), $result['>=']);

        $this->assertArrayHasKey('<', $result);
        $this->assertEquals(__('greater than'), $result['<']);

        $this->assertArrayHasKey('>', $result);
        $this->assertEquals(__('less than'), $result['>']);
    }

    public function testGetNewChildSelectOptions()
    {
        $type = 'test_type';
        $this->model->setType($type);

        $result = $this->model->getNewChildSelectOptions();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('value', $result);
        $this->assertEquals($type, $result['value']);

        $this->assertArrayHasKey('label', $result);
        $this->assertEquals(__('Up To Date'), $result['label']);
    }

    public function testGetValueElementType()
    {
        $this->assertEquals('text', $this->model->getValueElementType());
    }

    public function testGetSubfilterType()
    {
        $this->assertEquals('date', $this->model->getSubfilterType());
    }
}
