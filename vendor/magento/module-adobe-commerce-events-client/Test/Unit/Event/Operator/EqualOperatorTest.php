<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Operator;

use Magento\AdobeCommerceEventsClient\Event\Operator\EqualOperator;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EqualOperator class
 */
class EqualOperatorTest extends TestCase
{
    /**
     * @var EqualOperator
     */
    private EqualOperator $operator;

    protected function setUp(): void
    {
        $this->operator = new EqualOperator();
    }

    public function testWrongInputValueFormat(): void
    {
        $this->expectException(OperatorException::class);

        $this->operator->verify('test', ['some data']);
    }

    /**
     * @param string $ruleValue
     * @param $fieldValue
     * @param $expectedResult
     * @return void
     *
     * @throws OperatorException
     * @dataProvider verifyDataProvider
     */
    public function testVerify(string $ruleValue, $fieldValue, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->operator->verify($ruleValue, $fieldValue));
    }

    /**
     * @return array[]
     */
    public function verifyDataProvider(): array
    {
        return [
            ['categoryOne', 'categoryOne', true],
            ['categoryOn', 'categoryOne', false],
            ['33', 33, true],
            ['33.33', 33.33, true],
            ['33.33', 33.333, false],
        ];
    }
}
