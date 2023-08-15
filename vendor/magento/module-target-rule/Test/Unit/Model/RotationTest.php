<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\BaseTestCase;
use Magento\TargetRule\Model\Rotation;
use Magento\TargetRule\Model\Rule;

/**
 * Test rotation service
 */
class RotationTest extends BaseTestCase
{
    /**
     * @var Rotation
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = $this->objectManager->getObject(Rotation::class);
    }

    /**
     * Test reorder method with empty list
     *
     * @param int $rotation
     * @dataProvider reorderEmptyListDataProvider
     */
    public function testReorderEmptyList(int $rotation)
    {
        $list = [];
        $limit = 20;
        $this->assertEmpty($this->model->reorder($list, $rotation, $limit));
    }

    /**
     * Test reorder method with no rotation mode
     */
    public function testReorderByID()
    {
        $list = [
            44 => 1,
            33 => 2,
            11 => 2,
            22 => 1,
        ];
        $expected = [
            22 => 1,
            44 => 1,
            11 => 2,
            33 => 2,
        ];
        $rotation = Rule::ROTATION_NONE;
        $limit = 20;
        $this->assertSame($expected, $this->model->reorder($list, $rotation, $limit));
    }

    /**
     * Test reorder method with random mode
     */
    public function testReorderByRandom()
    {
        $list = [
            44 => 1,
            33 => 2,
            11 => 2,
            22 => 1,
        ];
        $rotation = Rule::ROTATION_SHUFFLE;
        $limit = 20;
        $result = $this->model->reorder($list, $rotation, $limit);
        $result = array_keys($result);
        $this->assertContains(44, array_slice($result, 0, 2));
        $this->assertContains(22, array_slice($result, 0, 2));
        $this->assertContains(33, array_slice($result, 2));
        $this->assertContains(11, array_slice($result, 2));
    }

    /**
     * Test reorder method with weighted random mode
     */
    public function testReorderByWeightedRandom()
    {
        $list = [
            44 => 1,
            33 => 2,
            11 => 2,
            22 => 1,
        ];
        $rotation = \Magento\TargetRule\Model\Source\Rotation::ROTATION_WEIGHTED_RANDOM;
        $limit = 20;
        $this->assertCount(4, $this->model->reorder($list, $rotation, $limit));
    }

    /**
     * @return array
     */
    public function reorderEmptyListDataProvider(): array
    {
        return [
            [
                Rule::ROTATION_NONE,
            ],
            [
                Rule::ROTATION_SHUFFLE,
            ],
            [
                \Magento\TargetRule\Model\Source\Rotation::ROTATION_WEIGHTED_RANDOM,
            ],
        ];
    }
}
