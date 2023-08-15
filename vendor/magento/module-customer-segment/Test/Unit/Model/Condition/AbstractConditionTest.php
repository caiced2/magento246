<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Condition;

use Magento\CustomerSegment\Model\Condition\AbstractCondition;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\TestCase;

class AbstractConditionTest extends TestCase
{
    /**
     * @var AbstractCondition
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
        $this->context = $this->getMockBuilder(
            Context::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceSegment =
            $this->createMock(Segment::class);

        $this->model = $this->getMockForAbstractClass(
            AbstractCondition::class,
            [
                $this->context,
                $this->resourceSegment,
            ]
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

    public function testGetMatchedEvents()
    {
        $result = $this->model->getMatchedEvents();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetResource()
    {
        $result = $this->model->getResource();

        $this->assertInstanceOf(Segment::class, $result);
        $this->assertEquals($this->resourceSegment, $result);
    }

    public function testGetDefaultOperatorOptions()
    {
        $result = $this->model->getDefaultOperatorOptions();

        $this->assertIsArray($result);

        $this->assertArrayHasKey('==', $result);
        $this->assertEquals('is', $result['==']);

        $this->assertArrayHasKey('!=', $result);
        $this->assertEquals('is not', $result['!=']);

        $this->assertArrayHasKey('>=', $result);
        $this->assertEquals('equals or greater than', $result['>=']);

        $this->assertArrayHasKey('<=', $result);
        $this->assertEquals('equals or less than', $result['<=']);

        $this->assertArrayHasKey('>', $result);
        $this->assertEquals('greater than', $result['>']);

        $this->assertArrayHasKey('<', $result);
        $this->assertEquals('less than', $result['<']);

        $this->assertArrayHasKey('{}', $result);
        $this->assertEquals('contains', $result['{}']);

        $this->assertArrayHasKey('!{}', $result);
        $this->assertEquals('does not contain', $result['!{}']);

        $this->assertArrayHasKey('()', $result);
        $this->assertEquals('is one of', $result['()']);

        $this->assertArrayHasKey('!()', $result);
        $this->assertEquals('is not one of', $result['!()']);

        $this->assertArrayHasKey('[]', $result);
        $this->assertEquals('contains', $result['[]']);

        $this->assertArrayHasKey('![]', $result);
        $this->assertEquals('does not contains', $result['![]']);
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
        $this->assertEquals(['==', '!=', '>=', '>', '<=', '<'], $result['numeric']);

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
}
