<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\ResourceModel;

use Magento\CustomerSegment\Model\ResourceModel\Helper;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var Helper|MockObject
     */
    private $helper;

    protected function setUp(): void
    {
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Helper(
            $this->resourceMock,
            ''
        );
    }

    /**
     * Check getSqlOperator() for all allowed operators
     *
     * @param string $operator
     * @param string $expected
     * @dataProvider dataProviderGetSqlOperator
     * @return void
     */
    public function testGetSqlOperator(
        $operator,
        $expected
    ) {
        $this->assertEquals($expected, $this->helper->getSqlOperator($operator));
    }

    /**
     * Data provider for testGetSqlOperator test case
     *
     * @return array
     */
    public function dataProviderGetSqlOperator()
    {
        return [
            [
                'operator' => '==',
                'expected' => '=',
            ],
            [
                'operator' => '!=',
                'expected' => '<>',
            ],
            [
                'operator' => '{}',
                'expected' => 'LIKE',
            ],
            [
                'operator' => '!{}',
                'expected' => 'NOT LIKE',
            ],
            [
                'operator' => '()',
                'expected' => 'IN',
            ],
            [
                'operator' => '!()',
                'expected' => 'NOT IN',
            ],
            [
                'operator' => '[]',
                'expected' => 'FIND_IN_SET(%s, %s)',
            ],
            [
                'operator' => '![]',
                'expected' => 'FIND_IN_SET(%s, %s) IS NULL',
            ],
            [
                'operator' => 'between',
                'expected' => 'BETWEEN %s AND %s',
            ],
            [
                'operator' => 'finset',
                'expected' => 'finset',
            ],
            [
                'operator' => '!finset',
                'expected' => '!finset',
            ],
            [
                'operator' => '>',
                'expected' => '>',
            ],
            [
                'operator' => '<',
                'expected' => '<',
            ],
            [
                'operator' => '>=',
                'expected' => '>=',
            ],
            [
                'operator' => '<=',
                'expected' => '<=',
            ],
        ];
    }

    /**
     * Check getSqlOperator() method in case when operator is not allowed
     */
    public function testGetSqlOperatorWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Unknown operator specified');
        $this->helper->getSqlOperator('.');
    }
}
