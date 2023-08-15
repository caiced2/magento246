<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Plugin;

use Magento\TargetRule\Model\Indexer\TargetRule\Plugin\Category;
use Magento\TargetRule\Model\Indexer\TargetRule\Plugin\Import;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @var Category
     */
    protected $_model;

    /**
     * @var Processor|MockObject
     */
    protected $_ruleProductMock;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|MockObject
     */
    protected $_productRuleMock;

    protected function setUp(): void
    {
        $this->_ruleProductMock = $this->createMock(
            Processor::class
        );
        $this->_productRuleMock = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_model = new Category(
            $this->_productRuleMock,
            $this->_ruleProductMock
        );
    }

    public function testCategoryChanges()
    {
        $subjectMock = $this->createMock(\Magento\Catalog\Model\Category::class);

        $subjectMock->expects($this->any())
            ->method('getData')
            ->willReturn(11);

        $this->_productRuleMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->_ruleProductMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterDelete($subjectMock)
        );

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterSave($subjectMock)
        );
    }

    /**
     * @param bool $schedule
     * @dataProvider invalidateIndexersDataProvider
     * @return void
     */
    public function testInvalidateIndexers($schedule)
    {
        $this->_productRuleMock->expects($this->once())
            ->method('isIndexerScheduled')
            ->willReturn($schedule);

        $this->_ruleProductMock->expects($this->once())
            ->method('isIndexerScheduled')
            ->willReturn($schedule);

        if (!$schedule) {
            $this->_productRuleMock->expects($this->once())
                ->method('markIndexerAsInvalid');

            $this->_ruleProductMock->expects($this->once())
                ->method('markIndexerAsInvalid');
        }

        $this->invokeMethod($this->_model, 'invalidateIndexers');
    }

    /**
     * Data provider for test 'testInvalidateIndexers'
     *
     * @return array
     */
    public function invalidateIndexersDataProvider()
    {
        return [
            'Update on save' => [
                '$schedule' => false,
            ],
            'Update by schedule' => [
                '$schedule' => true,
            ]
        ];
    }

    /**
     * @param Import $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
