<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Operator;

use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Operator\RegexOperator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see RegexOperator class
 */
class RegexOperatorTest extends TestCase
{
    /**
     * @var RegexOperator
     */
    private RegexOperator $operator;

    protected function setUp(): void
    {
        $this->operator = new RegexOperator();
    }

    public function testWrongInputValueFormat(): void
    {
        $this->expectException(OperatorException::class);

        $this->operator->verify('/test/', ['some data']);
    }

    public function testWrongPattern(): void
    {
        $this->expectException(OperatorException::class);

        $this->operator->verify('/test', 'test');
    }

    /**
     * @return void
     *
     * @dataProvider verifyDataProvider
     */
    public function testVerify(string $regex, $fieldValue, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->operator->verify($regex, $fieldValue));
    }

    /**
     * @return array[]
     */
    public function verifyDataProvider(): array
    {
        return [
            ['/.*test.*/', 'category', false],
            ['/.*test.*/', 'category-test', true],
            ['/.*3.*/', 33333, true],
            ['/.*4.*/', 33333, false],
            ['/.*4.*/', 3.5, false],
            ['/.*3.*/', 3.5, true],
        ];
    }
}
